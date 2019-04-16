# -*- coding: utf-8 -*-
# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
Spider specific scheduler for assigned jobs.
"""

import json
import logging
import os
import random
import re
import tempfile
import time
from collections import deque
from heapq import heappush, heappop, heapify

from pyparsing import Word, OneOrMore, Optional, Or, QuotedString, \
                      alphanums, alphas8bit, StringStart, StringEnd, \
                      nestedExpr, CharsNotIn, ParseException

from twisted.internet import defer, reactor, task, threads
from twisted.python import failure, log

from datahouse.monitoor import PeriodicJob, xfrms
from datahouse.monitoor.common import CrashStoppableService, \
                                      TransformationError
from datahouse.monitoor.process import Process, SMALL_PROCESS_TIMEOUT

# Interval in seconds for status log lines.
STATUS_LOG_INTERVAL = 60.0
PENDING_CHANGE_CHECK_INTERVAL = 900.0

# FIXME: should not be a global function here...
def count_words(txt):
    word_list = re.findall(r"[\w']+", txt)
    return len(word_list)

class Scheduler(CrashStoppableService):
    """ The scheduler connecting the db interface and the crawler.

    Takes care of triggering jobs at appropriate times and
    registering and unregistering jobs based on notifications from the
    database.
    """
    def __init__(self, dbi, crawler):
        """ @param: dbi: Instance of the MonDbInterface
            @param: crawler: Instance of the CrawlerService
        """
        self.dbi = dbi
        self.crawler = crawler

        crawler.registerNewDocumentCallback(self.retrievedDocument)

        self.registeredJobs = {}  # jobs by their id
        self.scheduledJobs = []   # use heappush and heappop
                                  # exclusively for this one

        self.nextJobCall = None

        self.statusTask = task.LoopingCall(self.logStatus)
        self.pendingChangesTask = task.LoopingCall(self.checkPendingChanges)
        self.pendingLiveTasks = {}

    def startService(self):
        self.statusTask.start(STATUS_LOG_INTERVAL)
        self.pendingChangesTask.start(PENDING_CHANGE_CHECK_INTERVAL)

    def stopService(self):
        if self.statusTask.running:
            self.statusTask.stop()
        if self.pendingChangesTask.running:
            self.pendingChangesTask.stop()
        if self.nextJobCall is not None and self.nextJobCall.active():
            self.nextJobCall.cancel()

    def logStatus(self):
        if self.nextJobCall is not None:
            assert(self.nextJobCall.active())
            log.msg("Total scheduled jobs: %d - next in %0.1f seconds."
                    % (len(self.scheduledJobs),
                       self.nextJobCall.getTime() - time.time()),
                    logLevel=logging.INFO, system="scheduler")
        elif len(self.scheduledJobs) > 0:
            log.msg("PROGRAMMING ERROR: %d scheduled jobs, but no processing call scheduled."
                    % (len(self.scheduledJobs),),
                    logLevel=logging.CRITICAL, system="scheduler")
        else:
            log.msg("Inactive - no scheduled jobs.",
                    logLevel=logging.INFO, system="scheduler")

    def checkPendingChanges(self):
        d = self.processPendingChanges()
        d.addErrback(self.crashStop, "programming error in scheduler")

    def scheduleJob(self, job, desc, now, delay):
        """ Add a single (already registered) job to the queue.
        """
        assert(delay >= 0.0)
        assert(job.id in self.registeredJobs)

        ts = now + delay
        if self.nextJobCall is None:
            self.nextJobCall = reactor.callLater(delay,
                                                 self.processScheduledJobs)
        elif self.scheduledJobs[0][0] > ts:
            self.nextJobCall.reset(delay)

        assert(self.nextJobCall.active())
        # The ts of the job to schedule should always be *after* the
        # next scheduled call to processScheduledJobs. However, we
        # accept a diff of a second.
        assert(ts - self.nextJobCall.getTime() >= -1.0)

        heappush(self.scheduledJobs, (ts, job))

    def unscheduleJob(self, job_id, now):
        """ Removes a single scheduled job from the queue.
        """
        assert(len(self.scheduledJobs) > 0)
        oldDelay = self.scheduledJobs[0][0]

        i = 0
        found = False
        while i < len(self.scheduledJobs):
            if self.scheduledJobs[i][1].id == job_id:
                ts, existingJob = self.scheduledJobs.pop(i)
                found = True
                break
            i += 1
        if not found:
            log.msg("Asked to remove a job that wasn't scheduled: %d" %
                    job_id, logLevel=logging.WARNING, system="scheduler")
            return

        # re-heapify, as the above pop may violate the heap's
        # constraints.
        heapify(self.scheduledJobs)

        # Possibly reschedule the job processor.
        if len(self.scheduledJobs) == 0:
            self.nextJobCall.cancel()
            self.nextJobCall = None
        elif oldDelay != self.scheduledJobs[0][0]:
            delay = self.scheduledJobs[0][0] - now
            self.nextJobCall.reset(delay)

    def processScheduledJobs(self):
        """ Process the next couple of jobs from the head of the
        scheduledJobs queue.
        """
        now = time.time()
        self.nextJobCall = None

        # Collect all jobs due within the next 1 second.
        immediates = deque()
        while len(self.scheduledJobs) > 0 and \
                self.scheduledJobs[0][0] - now < 1.0:
            _, job = heappop(self.scheduledJobs)
            assert(isinstance(job, PeriodicJob))
            immediates.append(job)
        assert(len(immediates) > 0)
        log.msg("Processing %d jobs: %s."
            % (len(immediates), repr([job.id for job in immediates])),
            logLevel=logging.DEBUG, system="scheduler")

        # Trigger the crawler for the given set of jobs.
        if len(immediates) > 0:
            self.crawler.triggerCrawl(immediates)

        # Schedule next call.
        if len(self.scheduledJobs) > 0:
            delay = self.scheduledJobs[0][0] - now
            self.nextJobCall = reactor.callLater(delay,
                                                 self.processScheduledJobs)

        # re-append the processed jobs to the queue
        for job in immediates:
            self.scheduleJob(job, 'existing', now, job.interval)

    def dropJobs(self, rows):
        now = time.time()
        for row in rows:
            if row['job_id'] in self.registeredJobs:
                existingJob = self.registeredJobs[row['job_id']]
                self.unscheduleJob(existingJob.id, now)
                del self.registeredJobs[existingJob.id]
            else:
                log.msg("dropJobs called for unknown job.",
                        logLevel=logging.WARNING, system="scheduler")

    def addJobs(self, rows):
        """ Called from the database layer for new or updated jobs.

        @param: row: a dict with at least the following keys: job_id, url,
        age, and min_check_interval
        """
        counters = {'new': 0, 'immediate': 0, 'assigned': 0, 'updated': 0}
        immediates = deque()
        now = time.time()
        for row in rows:
            job = PeriodicJob(row)
            if row['job_id'] in self.registeredJobs:
                # Job is already registered, update it.
                existingJob = self.registeredJobs[row['job_id']]
                assert(row['url'] == existingJob.url)
                if existingJob.interval != row['min_check_interval']:
                    existingJob.interval = row['min_check_interval']
                    # Reschedule the job.
                    self.unscheduleJob(existingJob.id, now)
                    delay = random.randint(0, row['min_check_interval'])
                    self.scheduleJob(existingJob, 'updated', now, delay)
                counters['updated'] += 1
            else:
                self.registeredJobs[row['job_id']] = job
                if row['age'] is None:
                    # A new job - defer the initial fetch to balance the load.
                    delay = random.randint(0, row['min_check_interval'])
                    self.scheduleJob(job, 'new', now, delay)
                    counters['new'] += 1
                elif row['age'] > row['min_check_interval']:
                    # A job that already lags behind. Schedule for
                    # immediate fetch.
                    self.scheduleJob(job, 'immediate', now, 0.0)
                    counters['immediate'] += 1
                else:
                    # A simple job to be scheduled for later fetch
                    self.scheduleJob(job, 'new', now, row['age'])
                    counters['assigned'] += 1

        if counters['updated'] > 0:
            log.msg("Updated %d job(s)" % counters['updated'],
                    logLevel=logging.INFO, system="scheduler")
        if counters['immediate'] == 0 and counters['assigned'] == 0:
            log.msg("Registered %d new job(s)" % counters['new'],
                    logLevel=logging.INFO, system="scheduler")
        else:
            log.msg("Registered %d new jobs, %d immediates, re-assigned %d"
                    % (counters['new'], counters['immediate'],
                       counters['assigned']),
                    logLevel=logging.INFO, system="scheduler")

    @defer.inlineCallbacks
    def processPendingChanges(self, jobId=None):
        res = yield self.dbi.getPendingChanges(jobId)
        # Not sure how come we can get a bool, here, but catch it
        # just like the shortcut below.
        if not res:
            return
        if len(res) == 0:         # short-circuit
            return
        changes = []
        for row in res:
            jobId, checkFreqId, xfrmId, pendingChangeIds, \
              providedTs, \
              oldDocId, oldDocContents, oldDocHash, \
              newDocId, newDocContents, newDocHash, \
              aggDelta, alertAttrs = row
            if oldDocHash is not None:
                oldDocHash = str(oldDocHash)
            if newDocHash is not None:
                newDocHash = str(newDocHash)
            if isinstance(alertAttrs, str) or \
                isinstance(alertAttrs, unicode):
                alertInfo = json.loads(alertAttrs)
            if isinstance(aggDelta, str) or \
                isinstance(aggDelta, unicode):
                aggDelta = json.loads(aggDelta)

            log.msg("processPendingChanges: jobId: %s, xfrmId: %d, pendingChangeIds: %s" % (
                    repr(jobId), xfrmId, repr(pendingChangeIds)),
                    logLevel=logging.INFO, system='scheduler')

            # Convert to string for JSON serializability.
            if providedTs is not None:
                providedTs = str(providedTs)

            # These conditions are actually SQL errors, but those are
            # somewhat hard to get 100% right for every corner case,
            # so we're fault-tolerant, here.
            if (oldDocHash is None and newDocHash is None \
              and aggDelta is None) \
              or alertAttrs is None:
                log.msg("Got invalid data from the database, ignoring row: %s"
                        % repr(row),
                        logLevel=logging.WARNING, system="scheduler")
                continue

            # ABA Problem: a change from A->B and one from B->A might
            # result in matching hashes.
            if oldDocHash == newDocHash and aggDelta is None:
                alertMatches = {}
                sections = []
            else:
                if aggDelta is None:
                    # Otherwise compare the two documents with diff.
                    change_fraction, sections = \
                        yield self.compareTransformedContent(oldDocContents,
                                                            newDocContents)
                else:
                    # transform additions of single strings to an
                    # array with just one string.
                    sections = []
                    for section in aggDelta:
                        if 'add' in section:
                            assert(not isinstance(section['add'], str))
                            if isinstance(section['add'], unicode):
                                section['add'] = [section['add']]
                        assert(isinstance(section['add'], list))

                        if 'del' in section:
                            assert(not isinstance(section['del'], str))
                            if isinstance(section['del'], unicode):
                                section['del'] = [section['del']]
                        assert(isinstance(section['del'], list))
                        sections.append(section)

                    # hard-coded change fraction for external data
                    change_fraction = 1.0

                # Hard-coded filter for the ABA case
                if change_fraction <= -0.1:
                    alertMatches = {}
                else:
                    alertMatches = self.applyFilters(
                        xfrmId, sections, change_fraction, alertAttrs)
                    # this returns a {"trigger": True, "matches":
                    # [...], "delta": [...]} kind of object per alert_id

            changes.append({
                'providedTs': providedTs,
                'pendingChangeIds': pendingChangeIds,
                'sections': sections,
                'alertMatches': alertMatches
                })

        #log.msg("materializing changes:")
        #for change in changes:
        #    log.msg("    change: %s" % repr(change))
        try:
            yield self.dbi.materializeChanges(changes)
        except Exception(e):
            log.msg("Failed to materialize changes, will repeat later",
                    logLevel=logging.INFO, system="scheduler")

    def retrievedDocument(self, document):
        if document.kind == 'job':
            return self.retrievedJobDocument(document)
        elif document.kind == 'task':
            return self.retrievedTaskDocument(document)
        else:
            assert(False)

    @defer.inlineCallbacks
    def retrievedJobDocument(self, document):
        # Use an external process to keep the main event loop clean of
        # CPU intensive tasks.
        try:
            document.hash = yield xfrms.calcFileHash(document.tmpFileName)
        except Exception, e:
            log.msg("Failed calculating file hash: %s" % e,
                    logLevel=logging.WARNING, system="scheduler")
            self.dbi.logError("Failed calculating file hash: %s" % str(e))
            defer.returnValue(None)

        log.msg("retrievedDocument: job %d - url %s" % (
            document.job.id, document.job.url),
            logLevel=logging.DEBUG, system='scheduler')

        # Update the job meta-data, which returns a list of
        # transformations to apply.
        res = yield self.dbi.updateJobMeta(document)
        dl = []
        for row in res:
            xfrmId, commands, xfrmArgs, \
              lastDocId, lastHash = row
            if lastHash is not None:
                lastHash = str(lastHash)
            if isinstance(xfrmArgs, str) or isinstance(xfrmArgs, unicode):
                xfrmArgs = json.loads(xfrmArgs)
            log.msg("retrievedDocument: job %d - xfrmId: %d, commands: %s, args: %s" % (
                document.job.id, xfrmId, commands, repr(xfrmArgs)),
                logLevel=logging.DEBUG, system='scheduler')
            assert(commands is not None)
            assert(len(commands.strip()) > 0)
            d = defer.maybeDeferred(self.setupXfrmChain,
                                        document, xfrmId, commands, xfrmArgs,
                                        lastDocId, lastHash)
            dl.append(d)

        res = yield defer.DeferredList(dl, consumeErrors=True)
        newDocIds = []
        for success, result in res:
            if success:
                assert(result is not None)
                xfrmId, contents, calcHash, mimeType, lastHash, lastDocId = result
                if calcHash != lastHash:
                    log.msg("retrievedDocument: storeNewDocument for job %d" % (
                                    document.job.id,),
                            logLevel = logging.DEBUG, system='scheduler')
                    newDocId = yield self.dbi.storeNewDocument(document.job.id,
                        xfrmId, contents, calcHash, mimeType, lastDocId)
                    newDocIds.append(newDocId)
            elif isinstance(result.value, TransformationError):
                log.msg("Transformation failed for job %d: %s" %
                        (document.job.id, str(result.value)),
                        logLevel=logging.WARNING, system="scheduler")
                for (pid, line) in result.value.getErrors():
                    log.msg(line, logLevel=logging.WARNING,
                        system="pid:%d" % pid)
                for (pid, line) in result.value.getWarnings():
                    log.msg(line, logLevel=logging.WARNING,
                        system="pid:%d")

                self.dbi.logError("Transformation failed for job %d" %
                                  document.job.id)
            else:
                log.msg("Exception during transformation of job %d: %s" % (
                    document.job.id, result.value),
                    logLevel=logging.WARNING, system="scheduler")
                self.dbi.logError("Exception during transformation of job %d: %s" % (
                    document.job.id, str(result)))

        # New pending_changes have possibly been created. Immediately
        # trigger a check for just this job_id. We might well be able
        # to eliminate all pending_changes.
        if len(newDocIds) > 0:
            yield self.processPendingChanges(document.job.id)

    @defer.inlineCallbacks
    def retrievedTaskDocument(self, document):
        document.hash = None
        lastHash = 'not-matching-the-above-in-any-case'

        assert(document.task_uuid in self.pendingLiveTasks)
        task_data = self.pendingLiveTasks[document.task_uuid]
        log.msg("retrievedDocument: for task %s (url %s)" % (
            document.task_uuid, task_data['url']),
            logLevel=logging.DEBUG, system='scheduler')
        for key, value in task_data.iteritems():
            log.msg("retrievedDocument:   %s => %s" % (
                key, repr(value)),
                logLevel=logging.DEBUG, system='scheduler')

        commands = 'xpath|html2markdown'
        xfrmId = -1
        xfrmArgs = {'xpath' : task_data['xpath']}
        lastDocId = None
        if task_data.has_key('xpath-ignore'):
            xfrmArgs['xpath-ignore'] = task_data['xpath-ignore']

        d = defer.maybeDeferred(self.setupXfrmChain,
                                document, xfrmId, commands, xfrmArgs,
                                lastDocId, lastHash)
        result = None
        try:
            result = yield d
        except failure.Failure as e:
            self.dbi.storeLiveTaskResult(document.task_uuid, {
                'success': False,
                'error': repr(e)})
            self.dbi.logError("Transformation failed: %s" % str(result.value))

        assert(result is not None)
        xfrmId, contents, calcHash, mimeType, lastHash, lastDocId = result
        log.msg("retrievedDocument: returning result for task %s" % (
                document.task_uuid,),
                logLevel = logging.DEBUG, system='scheduler')
        self.dbi.storeLiveTaskResult(document.task_uuid,
            {'success': True, 'data': contents})

        # mark the task as done
        del(self.pendingLiveTasks[document.task_uuid])

    def setupXfrmChain(self, document, xfrmId, commands, xfrmArgs, lastDocId,
                       lastHash):
        d = xfrms.planXfrms(document, commands, xfrmArgs)
        d.addCallback(self.xfrmChainCompleted,
                      document, xfrmId, lastDocId, lastHash)
        return d

    @defer.inlineCallbacks
    def xfrmChainCompleted(self, result, document, xfrmId, lastDocId,
                           lastHash):
        mustAbort, errors, warnings, data, mimeType = result
        assert(isinstance(xfrmId, int))
        if mustAbort:
            raise TransformationError("job %d, xfrmId %d, url %s" % (
                document.job.id, xfrmId, document.job.url),
                errors, warnings)
        calcHash = yield xfrms.calcStrHash(data)
        defer.returnValue((xfrmId, data, calcHash, mimeType, lastHash, lastDocId))

    @defer.inlineCallbacks
    def compareTransformedContent(self, oldContents, newContents):
        with tempfile.NamedTemporaryFile(delete=False) as f:
            f.write(str(oldContents))
            oldDocPath = f.name

        # Count words
        total_word_count = count_words(newContents)

        # Run it all through 'diff'.
        proc = Process(["diff",
                        "--minimal",
                        "--ignore-space-change",
                        "--ignore-blank-lines",
                        "--ignore-trailing-space",
                        "--ignore-all-space",
                        oldDocPath, "-"],
                       input=str(newContents), output=None,
                       timeout=SMALL_PROCESS_TIMEOUT)
        proc.setGoodExitCodes([0, 1])
        exitCode = yield proc.run()

        # Remove the temp file
        os.unlink(oldDocPath)

        diffError = proc.getErrStr()
        if len(diffError) > 0:
            log.msg("diff returned error outputs:")
            for line in diffError.split('\n'):
                log.msg(">>> " + line)

        changed_contents = ''
        if exitCode == 0:
            # shouldn't ever happen
            log.msg("diff returned exit code 0 " +
                    "likely only whitespace changes that we ignore",
                    logLevel=logging.DEBUG, system="scheduler")

            # lower than -0.1 value signifies ABA case
            change_fraction = -1.0
            changed_contents = ''
            defer.returnValue((change_fraction, changed_contents))
        elif exitCode != 1:
            log.msg("diff returned exit code %d - cannot scan for keywords"
                    % exitCode, logLevel=logging.WARNING, system="scheduler")
            raise Exception("diff error")
        else:
            diffOutput = proc.getOutStr()
            if not isinstance(diffOutput, unicode):
                diffOutput = diffOutput.decode('utf-8', "replace")

            sections = []
            current_section = None
            for line in diffOutput.split(u'\n'):
                if line == '':
                    continue
                elif line.startswith(u'---'):
                    continue
                elif line.startswith(u'> '):
                    assert(current_section is not None)
                    current_section['add'].append(line[2:])
                elif line.startswith('< '):
                    assert(current_section is not None)
                    current_section['del'].append(line[2:])
                else:
                    if current_section is not None:
                        sections.append(current_section)
                    current_section = {
                        'header': line,
                        'add': [],
                        'del': []
                        }
            if current_section is not None:
                sections.append(current_section)

            # Calculate changed_word_count.
            changed_word_count = 0
            for section in sections:
                additions = '\n'.join(section['add'])
                additions_wc = count_words(additions)
                removals = '\n'.join(section['del'])
                removals_wc = count_words(removals)
                changed_word_count = abs(additions_wc - removals_wc)

            # log word counts
            if total_word_count == 0:    # DIRTY HACK TO PREVENT DIV-0
                total_word_count = 1.0
            change_fraction = float(changed_word_count) / total_word_count
            log.msg("Counted %d changed words out of %d total, i.e. %0.1f%%" %
                    (changed_word_count,
                     total_word_count,
                     change_fraction * 100.0),
                    logLevel=logging.DEBUG, system='scheduler')

            defer.returnValue((change_fraction, sections))

    @staticmethod
    def searchNeedleInSection(needle, section):
        if section.has_key('add'):
            match_positions = []
            total_matches = 0
            for added_line in section['add']:
                ity = re.finditer(needle, added_line, re.I)
                mpos = [m.start() for m in ity]
                total_matches += len(mpos)
                match_positions.append(mpos)
            return total_matches, match_positions
        else:
            return 0, None

    @staticmethod
    def parseKeywordList(keyword_list):
        quoted_string = QuotedString(u'"', unquoteResults=False)
        word = CharsNotIn('\t\n "')
        grammar_phrase = Or((word, quoted_string))
        grammar_neg = Optional(u'-') + grammar_phrase
        grammar = StringStart() + OneOrMore(grammar_neg) + StringEnd()

        matched_tokens = grammar.parseString(keyword_list)
        search_terms = []
        next_token_negated = False
        for tok in matched_tokens:
            assert(isinstance(tok, unicode))
            if tok == u'-':
                next_token_negated = True
            else:
                search_terms.append((tok, next_token_negated))
                next_token_negated = False
        return search_terms

    @staticmethod
    def searchForKeywords(keyword_lists, sections):
        matches = []
        match_positions = {}
        for orig_keyword_list in keyword_lists:
            assert(isinstance(orig_keyword_list, unicode))
            keyword_list = orig_keyword_list
            keyword_list = keyword_list.strip().lower()
            keyword_list = keyword_list.replace(u',', u' ')
            keyword_list = keyword_list.replace(u';', u' ')
            if keyword_list == u'':
                continue
            if keyword_list.count(u'"') % 2 == 1:
                keyword_list += u'"'

            assert(isinstance(keyword_list, unicode))
            try:
                search_terms = Scheduler.parseKeywordList(keyword_list)
            except ParseException, e:
                log.msg("XXX: cannot parse keyword_list '%s' due to: %s" % (
                    repr(keyword_list), e))
                continue

            allKeywordsMatch = True
            negationMatches = False
            addMatchPos = {}
            for keyword, negated in search_terms:
                if keyword[0] in (u'"', u"'"):
                    needle = keyword[1:-1]
                else:
                    needle = keyword

                for i in range(len(sections)):
                    section = sections[i]
                    nhits, mpos = Scheduler.searchNeedleInSection(
                        needle, section)
                    if nhits > 0:
                        if i not in addMatchPos:
                            addMatchPos[i] = {}
                        addMatchPos[i][keyword] = mpos
                        if negated:
                            negationMatches = True
                    elif not negated:
                        allKeywordsMatch = False

            if allKeywordsMatch and not negationMatches:
                matches.append(orig_keyword_list)
                # merge addMatchPos into match_positions
                for i, perKeywordMatches in addMatchPos.iteritems():
                    for keyword, mpos in perKeywordMatches.iteritems():
                        if i not in match_positions:
                            match_positions[i] = {}
                        match_positions[i][keyword] = mpos

        return matches, match_positions

    def applyFilters(self, xfrmId, sections, change_fraction, alertAttrs):
        log.msg("applyFilters: start: alertAttrs: %s" % (
            repr(alertAttrs),),
            logLevel=logging.DEBUG, system='scheduler')
        alertMatches = {int(k): {'trigger': False}
                        for k in alertAttrs.keys()}
        for alertId, attrs in alertAttrs.iteritems():
            alertId = int(alertId)
            if attrs is None:
                # Special case no keywords defined at all, which
                # matches anything.
                alertMatches[alertId]['trigger'] = True
                log.msg("applyFilters: alertId %d: no filters, always triggering" % (
                    alertId,),
                    logLevel=logging.DEBUG, system='scheduler')
            else:
                keyword_lists = attrs['keyword_lists']
                threshold = attrs['threshold']

                log.msg("applyFilters: alertId %d: keyword_lists: %d and threshold: %s" % (
                    alertId, len(keyword_lists), repr(threshold)),
                    logLevel=logging.DEBUG, system='scheduler')
                for keyword_list in keyword_lists:
                    log.msg("alertId %d:     keyword_list: %s" % (
                        alertId, repr(keyword_list)),
                        logLevel=logging.DEBUG, system='scheduler')
                if len(keyword_lists) == 0:
                    if threshold is None:
                        alertMatches[alertId]['trigger'] = True
                    else:
                        alertMatches[alertId]['trigger'] \
                          = (change_fraction > threshold)
                else:
                    matches, match_positions = \
                      Scheduler.searchForKeywords(keyword_lists, sections)
                    log.msg("applyFilters: alertId %d: matches: %s" % (
                                alertId, repr(matches)),
                            logLevel=logging.DEBUG, system='scheduler')
                    if len(matches) > 0:
                        alertMatches[alertId]['trigger'] = True
                        alertMatches[alertId]['matches'] = matches
                        alertMatches[alertId]['match_positions'] = match_positions
        log.msg("applyFilters: alertId: %s, alertMatches: %s" % (
                alertId, repr(alertMatches)),
                logLevel=logging.DEBUG, system='scheduler')
        return alertMatches

    def processTestXpathTask(self, task_uuid, task_data):
        log.msg("got document for task xpath-test on %s (url: %s)" % (
            task_uuid, task_data['url']),
            logLevel=logging.DEBUG, system='scheduler')

        # verify task data
        assert('url' in task_data)
        assert('xpath' in task_data)

        self.pendingLiveTasks[task_uuid] = task_data
        self.crawler.triggerSingleUrlFetch(task_data['url'], task_uuid)
