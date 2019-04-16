# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
monitoor - the spider component of the datahouse.monitoor project
"""

class PeriodicJob:
    """ Simple struct representing a row in the spider_job table.
    """
    def __init__(self, row):
        self.id = row['job_id']
        self.url = row['url']
        self.interval = row['min_check_interval']

class RetrievedDocument:
    """ Simple struct representing a row in the spider_document table.
    """
    def __init__(self, kind, job, task_uuid, tmpFileName,
                 mediaType=None, mediaParams={}, date=None, entityTag=None):
        self.kind = kind
        self.job = job
        self.task_uuid = task_uuid
        self.tmpFileName = tmpFileName
        self.mediaType = mediaType
        self.mediaParams = mediaParams
        self.date = date
        self.entityTag = entityTag

__all__ = ['CrashStoppableService', 'CrawlerService', 'DbInterfaceService',
           'createMonApplication']

from datahouse.monitoor import xfrms
from datahouse.monitoor.app import createMonApplication
from datahouse.monitoor.crawler import CrawlerService
from datahouse.monitoor.dbapi import DbInterfaceService
from datahouse.monitoor.logfile import DailyLogFile, LevelFileLogObserver
from datahouse.monitoor.scheduler import Scheduler
