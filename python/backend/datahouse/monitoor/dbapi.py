# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
Database interface classes
"""

import json
import os
import psutil
import logging
from collections import deque

from twisted.internet import defer, reactor, task
from twisted.python import log

import psycopg2
import psycopg2.extras
from txpostgres import txpostgres, reconnection

from datahouse.monitoor.common import CrashStoppableService

UUID_FILE = 'monitoor.uuid'      # in the current dircectory...
OLD_UUID_FILE = 'monitor.uuid'   # former name, will be renamed
HEARTBEAT_INTERVAL = 30          # in seconds

JOBS_CHANNEL = 'spider_jobs_channel'
LIVE_CHANNEL = 'spider_live_task_channel'
RESULT_CHANNEL = 'spider_live_result_channel';

# This little snippet from the txpostgres documentation lets queries
# return dicts (rather than tuples).
def dict_connect(*args, **kwargs):
    kwargs['connection_factory'] = psycopg2.extras.DictConnection
    return psycopg2.connect(*args, **kwargs)
class DictConnection(txpostgres.Connection):
    """ a helper to get result rows that implement a dict
    """
    connectionFactory = staticmethod(dict_connect)

class ReconnectionHelper(reconnection.DeadConnectionDetector):
    """ a helper to pass dis-/reconnection events to the MonDbInterface
    """
    def __init__(self, helper):
        super(ReconnectionHelper, self).__init__()
        self.helper = helper

    def startReconnecting(self, f):
        log.msg("start (re)connecting",
                logLevel=logging.WARNING, system="db")
        self.helper.connectionDown()
        return reconnection.DeadConnectionDetector.startReconnecting(self, f)

    def connectionRecovered(self):
        log.msg("reconnected to the database",
                logLevel=logging.INFO, system="db")
        self.helper.connectionReEstablished()
        return reconnection.DeadConnectionDetector.connectionRecovered(self)

class RetriedTransaction:
    def __init__(self, name, helper, txnFunc, *args, **kwargs):
        self.name = name
        self.helper = helper
        self.txnFunc = txnFunc
        self.args = args
        self.kwargs = kwargs
        self.tries = 1
        self.delay = 1.0
        self.running = False
        self.completionDeferred = defer.Deferred()

    def run(self):
        self.triggerAttempt()
        return self.completionDeferred

    def triggerAttempt(self):
        assert(not self.running)
        if self.helper.connected:
            self.running = True
            d = self.helper.conn.runInteraction(self.txnFunc,
                                                *self.args, **self.kwargs)
            d.addCallbacks(self.attemptSucceeded, self.attemptFailed)

    def attemptFailed(self, failure):
        assert(self.running)
        self.running = False
        if isinstance(failure.value, psycopg2.ProgrammingError) or \
                isinstance(failure.value, NameError) or \
                isinstance(failure.value, TypeError):
            log.msg("FATAL: ProgrammingError in transaction %s:\n%s"
                    % (self.name, failure.value),
                    logLevel=logging.CRITICAL, system="db")
            self.completionDeferred.errback(failure)
        elif self.helper.connected:
            # schedule a retry - with an expenential back-off
            self.delay = self.delay * 2.0
            log.msg("retryTxn %s: failed, retrying in %0.0f seconds (error: %s)."
                    % (self.name, self.delay, str(failure)),
                    logLevel=logging.WARNING, system="db")
            self.tries += 1
            reactor.callLater(self.delay, self.triggerAttempt)

    def attemptSucceeded(self, result):
        self.completionDeferred.callback(result)

class DatabaseHelper:
    def __init__(self, dsn, errHandler):
        self.dsn = dsn
        self.errHandler = errHandler
        self.conn = DictConnection(detector=ReconnectionHelper(self))
        self.connected = False
        self.onConnectionStatusChange = None
        self.transactions = set()

    def connect(self):
        d = self.conn.connect(self.dsn)
        d.addErrback(self.conn.detector.checkForDeadConnection)
        d.addCallbacks(self.connectionEstablished, self.connectionFailed)
        return d

    def close(self):
        # FIXME: should wait for termination of pending transactions
        # (or abort them).
        if self.connected:
            return self.conn.close()
        else:
            return defer.succeed(None)

    def connectionEstablished(self, _):
        """ Called only once after startup.
        """
        log.msg("Connected to the database",
                logLevel=logging.INFO, system="db")
        self.connected = True
        if self.onConnectionStatusChange is not None:
            self.onConnectionStatusChange(True)

    def connectionReEstablished(self):
        """ Called after every successful re-connection to the database.
        """
        log.msg("Connection to the database reestablished",
                logLevel=logging.INFO, system="db")
        self.connected = True
        # create a copy, so we're free to change the set/list
        txn_list = list(self.transactions)
        for txn in txn_list:
            if not txn.running:
                txn.triggerAttempt()
        if self.onConnectionStatusChange is not None:
            self.onConnectionStatusChange(self.connected)

    def connectionDown(self):
        if self.connected:
            log.msg("Connection to the database has gone down",
                    logLevel=logging.WARNING, system="db")
            self.connected = False
            if self.onConnectionStatusChange is not None:
                self.onConnectionStatusChange(self.connected)

    def connectionFailed(self, failure):
        """ Panic handler for hard database connection failures.
        """
        log.msg("Initial database connection failed: %s" % failure.value,
                logLevel=logging.CRITICAL, system="db")
        if self.onInitialConnectionFailure is not None:
            self.onInitialConnectionFailure(failure)

    def retryTxn(self, name, func, *args, **kwargs):
        rtxn = RetriedTransaction(name, self, func, *args, **kwargs)
        self.transactions.add(rtxn)
        d = rtxn.run()
        d.addErrback(self.errHandler)
        d.addBoth(self.removeTransaction, rtxn)
        return d

    def slingshotTxn(self, name, func, *args, **kwargs):
        """ A misspelling for single shot transaction, i.e. without retries,
            but not entirely unlike a slingshot, either.

            Returns false directly, if not connected. Otherwise a deferred
            returning whether or not the transaction succeeded.
        """
        # Prevent running any transactions before the initial connection
        # attempt of the helper itself.
        if not self.connected:
            return defer.fail(reconnection.ConnectionDead())
        else:
            d = self.conn.runInteraction(func, *args, **kwargs)
            d.addErrback(self.slingshotFailed, name)
            return d

    def slingshotFailed(self, failure, name):
        log.msg("slingshot %s: failed (ignoring), errors: %s"
                % (name, repr(failure.value)))
        return False

    def removeTransaction(self, result, rtxn):
        self.transactions.remove(rtxn)
        return result


class DbInterfaceService(CrashStoppableService):
    """ Database Interface for the Monitoor Spider

    Features a single, steady database connection. Takes care of
    reconnecting after a connection loss and does a regular heartbeat,
    so the central server can eventually detect failed spiders.
    """
    def __init__(self, dsn, uuid=None):
        """
        @param: dsn: Connection string passed on to txpostgres.
        @param: uuid: this spider instance's uuid, if assigned.
        """
        self.dbh = DatabaseHelper(dsn, self.fatalTxnError)
        self.dbh.onConnectionStatusChange = self.connStatusChange
        self.dbh.onInitialConnectionFailure = self.initialConnectionFailed

        self.dbh.conn.addNotifyObserver(self.gotNotification)

        self.initialized = False
        self.uuid = None
        try:
            # For consistency, we even rename the UUID file.
            if os.path.exists(OLD_UUID_FILE):
                assert(not os.path.exists(UUID_FILE))
                os.rename(OLD_UUID_FILE, UUID_FILE)
            if os.path.exists(UUID_FILE):
                with open(UUID_FILE, 'r') as f:
                    self.uuid = f.read().strip()
                    log.msg("Read spider identification UUID: %s." % self.uuid,
                            logLevel=logging.INFO, system="db")
        except IOError, e:
            log.msg("Error reading the spider identification UUID: %s" % e,
                    logLevel=logging.ERROR, system="db")

        self.heartbeatTask = task.LoopingCall(self.heartbeat)

        # List of callbacks for hooking with the scheduler.
        self.addJobsCallbacks = []
        self.dropJobsCallbacks = []
        self.liveTaskCallbacks = {}
        self.highestChangeProcessed = 0
        self.pendingNotifications = deque()
        self.processingNotifications = False

    def startService(self):
        self.running = True
        return self.dbh.connect()

    def stopService(self):
        self.deinitialize()
        self.running = False
        self.dbh.close()

    def fatalTxnError(self, failure):
        return self.crashStop(failure, "database transaction")

    def connStatusChange(self, connected):
        if connected:
            assert(not self.initialized)
            self.initialize()
        else:
            # disconnected
            if self.initialized:
                self.deinitialize()

    def initialConnectionFailed(self, failure):
        return self.crashStop(failure, "initial connection")

    def heartbeat(self):
        """ Called regularly to update the last_seen_ts for this spider to
        ensure it's considered alive.
        """
        load_one, load_five, load_fifteen = os.getloadavg()
        cpu_times = psutil.cpu_times(percpu=False)
        io_counters = psutil.net_io_counters(pernic=False)

        irq_time = cpu_times.irq
        if hasattr(irq_time, 'softirq'):
            irq_time += cpu_times.softirq

        d = self.dbh.conn.runQuery("""
          SELECT spider_heartbeat(%(uuid)s::uuid, %(fqdn)s,
            %(load_one)s, %(load_five)s, %(load_fifteen)s,
            %(cpu_user_time)s, %(cpu_system_time)s, %(cpu_idle_time)s,
            %(cpu_idle_time)s, %(cpu_iowait_time)s, %(cpu_irq_time)s,
            %(bytes_sent)s, %(bytes_recv)s,
            %(packets_sent)s, %(packets_recv)s
          );""",
            {
                'uuid': self.uuid,
                'fqdn': '',
                'load_one': load_one,
                'load_five': load_five,
                'load_fifteen': load_fifteen,
                'cpu_user_time': cpu_times.user,
                'cpu_system_time': cpu_times.system,
                'cpu_idle_time': cpu_times.idle,
                'cpu_iowait_time': cpu_times.iowait,
                'cpu_irq_time': irq_time,
                'bytes_sent': io_counters.bytes_sent,
                'bytes_recv': io_counters.bytes_recv,
                'packets_sent': io_counters.packets_sent,
                'packets_recv': io_counters.packets_recv,
            }
        )
        d.addCallback(self.assignUUID)
        d.addErrback(self.ignoreDeadConnection)
        d.addErrback(self.heartbeatFailed)
        return d

    def assignUUID(self, result):
        """
        Callback for the heartbeat to the database. Retrieves the UUID assigned
        by the database to this spider, if we didn't provide one, already.
        """
        retval = result[0][0]
        if retval is not None:
            if self.uuid is not None:
                log.err("This spider's UUID wasn't recognized by the database.")
                log.err("Using a new UUID. All job formerly assigned to this spider will need to be re-assigned.")
            self.uuid = result[0][0]
            log.msg("This spider instance got UUID %s." % self.uuid,
                    logLevel=logging.INFO, system="db")
            try:
                with open(UUID_FILE, 'w') as f:
                    f.write("%s\n" % self.uuid)
            except IOError, e:
                log.err("Error saving the spider identification UUID: %s" % e)

        # If the backend didn't have a UUID at startup, it couldn't
        # initialize. In that case, it needs to initialize, here.
        if not self.initialized:
            self.initialize()

    def ignoreDeadConnection(self, failure):
        """ Add as an error handler to ignore connection failures, the spider will
        reconnect, anyways.
        """
        failure.trap(reconnection.ConnectionDead)

    def heartbeatFailed(self, failure):
        """ Panic handler for hard failures of the heartbeat update.
        """
        log.err("Heartbeat to the database failed: %s" % failure)
        return self.crashStop(failure, "database heartbeat")

    def initialize(self):
        """ Called initialy as well as after re-connecting to the database.
        """
        assert(not self.initialized)

        # We start the heartbeat in any case. This will trigger the
        # database connection and UUID generation for the backend.
        self.heartbeatTask.start(HEARTBEAT_INTERVAL)

        # If we don't have a UUID already, we need to wait for the heartbeat
        # to fetch one and continue with initialization after that.
        if self.uuid is not None:
            # Strictly speaking, the spider is still not fully initialized
            # at this point in time, but the variable just needs to ensure
            # initialization is triggered only once.
            self.initialized = True

            log.msg("initialize: fetchInitialJobs")
            d = self.fetchInitialJobs()
            d.addErrback(self.ignoreDeadConnection)
            d.addErrback(self.initializationFailed)

    @defer.inlineCallbacks
    def startListening(self, channel_name, attempt_no=1):
        sql = "LISTEN %s;" % channel_name
        try:
            yield self.dbh.conn.runOperation(sql)
            log.msg("Listening on %s" % channel_name)
        except reconnection.ConnectionDead, e:
            if attempt_no >= 7:
                self.crashStop(e, "failed to listen")
            else:
                d = defer.Deferred()
                d.addCallback(self.startListening, attempt_no + 1)
                reactor.callLater(1.0, d.callback, channel_name)
                yield d

    @defer.inlineCallbacks
    def fetchInitialJobs(self):
        """ Queries the database for assigned jobs upon startup or reconnection.
        """
        conn = self.dbh.conn

        # Start listening for notifications, first. That way, the
        # spider can be pretty sure to not lose any notification between
        # its initial scan and the first notification retrieved. However,
        # it needs to take care to eliminate duplicates.
        for channel_name in [JOBS_CHANNEL, LIVE_CHANNEL]:
            self.startListening(channel_name)

        yield self.dbh.retryTxn("rebalance", self.rebalanceAndUpdateJobs)

        # Then check the currently highest change id. For all later
        # changes (with higher ids), we'll receive a notification.
        query = "SELECT max(change_id) AS max FROM spider_change;"
        res = yield conn.runQuery(query)
        self.highestChangeProcessed = res[0]['max']

        query = """ SELECT job_id, url, url_lang, min_check_interval,
                           extract('epoch' FROM now() - last_check_ts) AS age
                    FROM spider_job
                    WHERE job_spider_uuid = %(uuid)s AND job_active; """
        res = yield conn.runQuery(query, {'uuid': self.uuid})

        # Trigger the scheduler on the jobs received.
        for cb in self.addJobsCallbacks:
            yield defer.maybeDeferred(cb, res)

    def initializationFailed(self, failure):
        log.msg("Initialization failed: %s" % failure,
                logLevel=logging.CRITICAL, system="db")
        self.deinitialize()
        # Thanks to the heartbeat, we well retry initialization.

    def deinitialize(self):
        if self.heartbeatTask.running:
            self.heartbeatTask.stop()
        self.initialized = False
        self.initialized = False

    def gotNotification(self, notify):
        if notify.channel == JOBS_CHANNEL:
            self.pendingNotifications.append(notify)
            if not self.processingNotifications:
                self.processNotifications()
        elif notify.channel == LIVE_CHANNEL:
            self.processLiveTaskQueue()
        else:
            log.msg("Ignored notification on channel %s" % repr(notify.channel),
                    logLevel=logging.DEBUG, system="db")

    @defer.inlineCallbacks
    def processNotifications(self):
        self.processingNotifications = True
        while len(self.pendingNotifications) > 0:
            # We don't need to process every single notification, but
            # can combine all notifications accumulated so far at
            # once. However, every notification after this point in
            # time needs to trigger another processing step.
            self.pendingNotifications.clear()
            yield self.dbh.retryTxn("rebalance", self.rebalanceAndUpdateJobs)

        self.processingNotifications = False

    @defer.inlineCallbacks
    def processLiveTaskQueue(self):
        while True:
            task = yield self.dbh.retryTxn('live-fetch', self._fetchLiveTask)
            if task is None:
                break;
            if task['kind'] not in self.liveTaskCallbacks:
                log.msg("Ignoring notification on channel %s: no handler for task %s registered" % (
                    LIVE_CHANNEL, task['kind']),
                    logLevel=logging.CRITICAL, system="db")
            for cb in self.liveTaskCallbacks[task['kind']]:
                yield defer.maybeDeferred(cb, task['uuid'], task['data'])

    @defer.inlineCallbacks
    def rebalanceAndUpdateJobs(self, cur):
        """ Notification handler, called by the DB/API via NOTIFY whenever a
            new URL is added or when an existing one is changed or dropped.
        """
        # Assign and rebalance jobs between backends.
        log.msg("rebalancing jobs", logLevel=logging.DEBUG, system="db")
        yield cur.execute("SELECT spider_rebalance_jobs();")

        log.msg("query for changes", logLevel=logging.DEBUG, system="db")
        # Then query for changes in jobs assigned to this backend.
        query = """ SELECT job_id, url, url_lang, min_check_interval, age,
                            MAX(change_id) AS max_change_id,
                            change_type_agg(change_type) AS change_type
                    FROM spider_job_change
                    WHERE change_id > %(highestChangeId)s
                        AND job_spider_uuid = %(uuid)s
                    GROUP BY job_id, url, url_lang,
                            min_check_interval, age;
                    """
        params = {'highestChangeId': self.highestChangeProcessed,
                  'uuid': self.uuid}
        yield cur.execute(query, params)
        res = yield cur.fetchall()

        isInitialRun = (self.highestChangeProcessed == 0)
        newMax = self.highestChangeProcessed

        addedRows = []
        droppedRows = []
        for row in res:
            if row['change_type'] in ('insert', 'update'):
                addedRows.append(row)
            elif row['change_type'] == 'delete':
                if not isInitialRun:
                    droppedRows.append(row)
            else:
                assert(False)
            newMax = max(row['max_change_id'], newMax)

        # Trigger the scheduler on the jobs changed.
        if len(droppedRows) > 0:
            for cb in self.dropJobsCallbacks:
                yield defer.maybeDeferred(cb, droppedRows)

        if len(addedRows) > 0:
            for cb in self.addJobsCallbacks:
                yield defer.maybeDeferred(cb, addedRows)

        # Adjust the highestChangeProcessed only *after* all of the
        # above succeeded.
        self.highestChangeProcessed = newMax

    def registerAddJobsCallback(self, cb):
        assert(callable(cb))
        self.addJobsCallbacks.append(cb)

    def registerDropJobsCallback(self, cb):
        assert(callable(cb))
        self.dropJobsCallbacks.append(cb)

    def registerLiveTaskCallback(self, kind, cb):
        assert(callable(cb))
        if kind not in self.liveTaskCallbacks:
            self.liveTaskCallbacks[kind] = []
        self.liveTaskCallbacks[kind].append(cb)

    def logError(self, errmsg):
        return self.dbh.retryTxn("log-error", self._logError, errmsg)

    def _logError(self, cur, errmsg):
        return cur.execute(""" INSERT INTO spider_errlog (spider_uuid, msg)
                               VALUES (%(uuid)s, %(errmsg)s); """,
                           {'uuid': self.uuid,
                            'errmsg': errmsg})

    def getPendingChanges(self, jobId=None):
        """ returns tuples of the form:
            (job_id, check_frequency_id, xfrm_id, pending_change_ids JSON,
             old_doc_id, old_doc_contents, old_doc_contents_hash,
             new_doc_id, new_doc_contents, new_doc_contents_hash,
             alert_attrs JSON)
        """
        d = self.dbh.slingshotTxn("pending-changes",
                                  self._getPendingChanges, jobId)
        # Ignory any errors and return an empty list, in those cases.
        d.addErrback(lambda failure: [])
        return d

    @defer.inlineCallbacks
    def _getPendingChanges(self, cur, jobId):
        yield cur.execute(
            """ SELECT * FROM get_pending_changes_for(now(), %(jobid)s); """,
            {'jobid': jobId})
        res = yield cur.fetchall()
        defer.returnValue(res)

    def materializeChanges(self, changes):
        return self.dbh.slingshotTxn("mat-changes",
                                     self._materializeChanges, changes)

    @defer.inlineCallbacks
    def _materializeChanges(self, cur, changes):
        res = yield cur.execute(
            """ SELECT *
                FROM materialize_changes(%(changes)s, NULL,
                                         %(uuid)s, now()); """,
            {'changes': json.dumps(changes),
             'uuid': self.uuid})
        res = yield cur.fetchall()
        defer.returnValue(res)

    def updateJobMeta(self, document):
        """ returns tuples of the form:
            (xfrm_id, commands, args,
             latest_doc_id, latest_doc_contents_hash)
        """
        return self.dbh.retryTxn("add-meta-%d" % document.job.id,
                                 self._updateJobMeta, document)

    def _updateJobMeta(self, cur, document):
        d = cur.execute(
            """ SELECT * FROM spider_update_job_meta(%(job_id)s,
                                                     %(date)s,
                                                     %(entityTag)s,
                                                     %(hash)s); """,
                {'job_id': document.job.id,
                 'date': document.date,
                 'entityTag': document.entityTag,
                 'hash': psycopg2.Binary(document.hash)})

        return d.addCallback(lambda res: res.fetchall())

    def fetchOldDocument(self, docId):
        return self.dbh.retryTxn("fetch-doc-%d" % (docId,),
                                 self._fetchOldDocument, docId)

    @defer.inlineCallbacks
    def _fetchOldDocument(self, cur, docId):
        res = yield cur.execute(
            """ SELECT contents FROM spider_document
                WHERE spider_document_id = %(docId)s;
            """,
            {
                'docId': docId
            })
        res = yield cur.fetchall()
        rows = list(res)
        if len(res) == 0:
            defer.returnValue(None)
        elif len(res) == 1:
            defer.returnValue(rows[0][0])
        else:
            assert(False)

    def storeNewDocument(self, jobId, xfrmId, *args):
        """ Called from the scheduled every time a change is detected for a
        certain job. Stores the document and appropriate notifications in the
        database.

        @rtype: Deferred for txn completion
        """
        return self.dbh.retryTxn("store-doc-%d-%s" % (jobId, xfrmId),
                                 self._storeNewDocument, jobId, xfrmId, *args)

    @defer.inlineCallbacks
    def _storeNewDocument(self, cur, jobId, xfrmId, contents, contentsHash,
                          contentsMimeType, lastDocId):
        res = yield cur.execute(
            """ SELECT * FROM spider_store_document(%(job_id)s,
                                                    %(xfrm_id)s,
                                                    %(old_doc_id)s,
                                                    %(spider_uuid)s,
                                                    %(contents)s,
                                                    %(contents_hash)s,
                                                    %(contents_mime_type)s,
                                                    now()); """,
            {'job_id': jobId,
             'xfrm_id': xfrmId,
             'old_doc_id': lastDocId,
             'spider_uuid': self.uuid,
             'contents': psycopg2.Binary(contents),
             'contents_hash': psycopg2.Binary(contentsHash),
             'contents_mime_type': contentsMimeType})
        res = yield cur.fetchall()
        newDocIds = []
        for row in res:
            newDocId, = row
            newDocIds.append(newDocId)
            log.msg("Stored document %s for job %d." % (newDocId, jobId),
                    logLevel=logging.INFO, system="db")
        assert(len(newDocIds) == 1)
        defer.returnValue(newDocIds[0])

    @defer.inlineCallbacks
    def _fetchLiveTask(self, cur):
        res = yield cur.execute(
            """ SELECT *
                FROM dequeue_live_task(%(spider_uuid)s);
            """,
            {
                'spider_uuid': self.uuid
            })
        res = yield cur.fetchall()
        rows = list(res)
        assert(len(res) == 1)
        data = rows[0][0]
        defer.returnValue(data)

    def storeLiveTaskResult(self, *args):
        return self.dbh.retryTxn("store-live-task-result",
                                 self._storeLiveTaskResult, *args)

    @defer.inlineCallbacks
    def _storeLiveTaskResult(self, cur, task_uuid, result):
        yield cur.execute(
            """ INSERT INTO live_task_result (
                    task_uuid, task_result, spider_uuid
                ) VALUES (
                    %(task_uuid)s, %(result)s::JSONB, %(spider_uuid)s
                ); """,
            {
                'task_uuid': task_uuid,
                'result': json.dumps(result),
                'spider_uuid': self.uuid
            })
        yield cur.execute("NOTIFY %s;" % RESULT_CHANNEL)
        defer.returnValue(None)
