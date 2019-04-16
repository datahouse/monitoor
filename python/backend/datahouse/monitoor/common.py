# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
import logging

from twisted.application import service
from twisted.internet import defer, reactor
from twisted.python import log

class TransformationError(Exception):
    def __init__(self, msg, errors, warnings):
        super(Exception, self).__init__(msg)
        self.errors = errors
        self.warnings = warnings

    def getErrors(self):
        return self.errors

    def getWarnings(self):
        return self.warnings

class CrashStoppableService(service.Service):
    """ A helper class for services which may stop the entire application upon
        failure.
    """
    @defer.inlineCallbacks
    def crashStop(self, failure, msg):
        log.msg("Fatal error in %s: %s" % (failure, msg),
                logLevel=logging.CRITICAL)
        yield defer.maybeDeferred(self.parent.stopService)
        if reactor.running:
            reactor.stop()
        # doesn't get called back, i.e. crashStop doesn't ever return
        yield defer.Deferred()

    def cleanupTempFile(self, result, tmpFileName):
        os.unlink(tmpFileName)
        return result
