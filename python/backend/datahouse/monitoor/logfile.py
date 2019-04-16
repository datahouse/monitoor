# -*- coding: utf-8 -*-
# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
Twisted's DailyLogFile class emits logfiles that are not properly
sortable. Provide a class that overrides the logfile format.
"""

import logging

from twisted.python.logfile import DailyLogFile as OrigDailyLogFile
from twisted.python.log import FileLogObserver

class DailyLogFile(OrigDailyLogFile):
    def suffix(self, tupledate):
        """ Return the suffix given a (year, month, day) tuple or unixtime
        """
        if isinstance(tupledate, float):
            tupledate = self.toDate(tupledate)
        return '%04d%02d%02d' % tupledate


class LevelFileLogObserver(FileLogObserver):
    def __init__(self, f, level=logging.INFO):
        FileLogObserver.__init__(self, f)
        self.logLevel = level

    def emit(self, eventDict):
        if eventDict['isError']:
            level = logging.ERROR
        elif 'logLevel' in eventDict:
            level = eventDict['logLevel']
        else:
            level = logging.INFO
        if level >= self.logLevel:
            FileLogObserver.emit(self, eventDict)
