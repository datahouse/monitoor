# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>
#
# -*- test-case-name: datahouse.monitoor.test.test_process -*-

"""
Helper class for management of external processes.
"""

import logging
import os
import signal
import time

try:
    # Python 2.6 / 2.7
    import cStringIO as StringIO
except ImportError:
    # Python 3
    from io import StringIO

from twisted.internet import defer, protocol, reactor
from twisted.python import log

SMALL_PROCESS_TIMEOUT = 60.0
LARGE_PROCESS_TIMEOUT = 600.0

class InvalidUse(ValueError):
    pass

class ProcessTimedOutError(RuntimeError):
    pass

class ProcessProtocol(protocol.ProcessProtocol):
    def __init__(self, parent, inputData):
        self.parent = parent
        self.outbuf = StringIO.StringIO()
        self.errbuf = StringIO.StringIO()
        self.inputData = inputData

    def connectionMade(self):
        if self.inputData is not None:
            self.transport.writeToChild(0, self.inputData)
            self.transport.closeChildFD(0)
        else:
            self.parent.closeFds(None)

    def outReceived(self, data):
        self.outbuf.write(data)

    def errReceived(self, data):
        self.errbuf.write(data)

    def processEnded(self, reason):
        runtime = time.time() - self.parent.startTime
        if reason.value.exitCode == 0:
            self.parent.appendDebugMessage(
                "process ended (runtime %0.1f)" % runtime)
        elif reason.value.exitCode is None:
            self.parent.appendDebugMessage(
                "process aborted (no exit code, runtime %0.1f)" % (runtime,))
        else:
            self.parent.appendDebugMessage(
                "process ended (exit code %d, runtime %0.1f)" % (
                    reason.value.exitCode, runtime))
        self.parent.childProcessEnded(reason.value.exitCode)

class Process:
    def __init__(self, args, input=None, output=None, timeout=None):
        self.args = args
        self.completionDeferred = defer.Deferred()
        self.completionDeferred.addCallback(self.emitDebugMessages)
        self.input = input
        self.output = output
        self.timeout = timeout
        self.timeoutCall = None
        self.processTimedOut = False
        if isinstance(input, int) or isinstance(input, file):
            input = None
        self.pid = -1
        self.protocol = ProcessProtocol(self, input)
        self.debugMsgs = []
        self.goodExitCodes = [0]

    def setGoodExitCodes(self, goodExitCodes):
        self.goodExitCodes = goodExitCodes

    def appendDebugMessage(self, msg):
        self.debugMsgs.append({
            'system': 'pid:%s' % self.pid,  # can be None
            'msg': msg,
            'time': time.time()
        })

    def emitDebugMessages(self, exitCode):
        if exitCode not in self.goodExitCodes:
            for dm in self.debugMsgs:
                msg = \
                    time.strftime('%Y-%m-%d %H:%M:%S',
                                  time.localtime(dm['time'])) + \
                    ' ' + time.tzname[0] + ': ' + \
                    dm['msg']
                log.msg(msg, logLevel=logging.WARNING,
                        system="pid:%d" % self.pid)
        return exitCode

    def run(self):
        assert(not self.completionDeferred.called)
        self.startTime = time.time()
        if self.timeout is not None:
            self.timeoutCall = reactor.callLater(
                float(self.timeout),
                self.killProcess)
        childFDs = {0: 'w', 1:'r', 2:'r'}
        if isinstance(self.input, file):
            childFDs[0] = self.input.fileno()
        elif isinstance(self.input, int):
            childFDs[0] = self.input
        if isinstance(self.output, file):
            childFDs[1] = self.output.fileno()
        elif isinstance(self.output, int):
            childFDs[1] = self.output
        self.process = reactor.spawnProcess(self.protocol, self.args[0],
            args=self.args, env={'HOME': os.environ['HOME'],
                                 'LC_ALL': 'C'},
            path=None, usePTY=False, childFDs=childFDs)
        self.pid = self.process.pid
        self.appendDebugMessage("started process: %s (childFDs: %s)" % (
                                    repr(self.args), repr(childFDs)))
        return self.completionDeferred

    def childProcessEnded(self, exitCode):
        """ Called from the ProcessProtocol above upon correct termination
            of the process.
        """
        if self.timeoutCall is not None:
            self.timeoutCall.cancel()
            self.timeoutCall = None

        if self.processTimedOut:
            self.completionDeferred.errback(ProcessTimedOutError("Process " +
                " ".join(self.args) + " timed out"))
        else:
            self.completionDeferred.callback(exitCode)

    def killProcess(self, invocationCnt=1):
        """ Called if the process didn't terminate after the given timeout.
        """
        self.processTimedOut = True
        if invocationCnt == 1:
            self.appendDebugMessage("timed out, trying to terminate.")
            try:
                os.kill(self.pid, signal.SIGTERM)
            except OSError:
                pass
            self.timeoutCall = reactor.callLater(15.0, self.killProcess, 2)
        else:
            self.appendDebugMessage("timed out, killing process.")
            try:
                os.kill(self.pid, signal.SIGKILL)
            except OSError:
                pass
            self.timeoutCall = reactor.callLater(
                15.0,
                self.killProcess,
                invocationCnt + 1)

    def closeFds(self, result):
        if isinstance(self.input, file):
            self.input.close()
        elif isinstance(self.input, int):
            os.close(self.input)
        if isinstance(self.output, file):
            self.output.close()
        elif isinstance(self.output, int):
            os.close(self.output)
        return result

    def getOutStr(self):
        if self.output is not None:
            raise InvalidUse(
                "Cannot use getOutStr when redirecting to a file.")
        return self.protocol.outbuf.getvalue()

    def getErrStr(self):
        return self.protocol.errbuf.getvalue()
