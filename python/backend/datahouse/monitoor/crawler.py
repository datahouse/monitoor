# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
Spider class(es) for the monitoor project.
"""

import logging
import os
import json
import tempfile

from twisted.internet import defer, reactor
from twisted.python import log

from scrapy.crawler import Crawler
from scrapy.settings import Settings

from datahouse.monitoor import RetrievedDocument
from datahouse.monitoor.common import CrashStoppableService
from datahouse.monitoor.scheduler import Scheduler
from datahouse.monitoor.spider import Spider

class CrawlerService(Crawler, CrashStoppableService):
    def __init__(self):
        """
        A custom Crawler integrated into the twisted Application framework.

        @param: settings: scrapy Settings object
        """
        settings = Settings({
            # Override some of the extensions enabled by default, as these
            # are not needed or simply don't work, because we close the
            # Spider after every request. (Disabled ones commented out.)
            'EXTENSIONS_BASE': {
                'scrapy.contrib.corestats.CoreStats': 0,
                #'scrapy.webservice.WebService': 0,
                #'scrapy.telnet.TelnetConsole': 0,
                'scrapy.contrib.memusage.MemoryUsage': 0,
                'scrapy.contrib.memdebug.MemoryDebugger': 0,
                #'scrapy.contrib.closespider.CloseSpider': 0,
                #'scrapy.contrib.feedexport.FeedExporter': 0,
                'scrapy.contrib.logstats.LogStats': 0,
                #'scrapy.contrib.spiderstate.SpiderState': 0,
                'scrapy.contrib.throttle.AutoThrottle': 0,
                },

            'HTTPCACHE_ENABLED': True,
            'HTTPCACHE_DIR': '/tmp/httpcache',
            'HTTPCACHE_POLICY': 'scrapy.contrib.httpcache.RFC2616Policy',
            'HTTPCACHE_STORAGE': 'scrapy.contrib.httpcache.FilesystemCacheStorage',
            'HTTPCACHE_EXPIRATION_SECS': 1, # one second
            'USER_AGENT': "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
        })
        super(CrawlerService, self).__init__(settings)
        self.configure()

        self.spider = Spider()
        self.spider.set_crawler(self)
        d = self.engine.open_spider(self.spider, close_if_idle=False)
        d.addErrback(self.scrapyEngineFailure)

        self.newDocCallback = None
        self.pendingRequests = {}

    def startService(self):
        return self.start()

    def stopService(self):
        return self.stop()

    def scrapyEngineFailure(self, failure):
        log.msg("Scrapy Engine failure: %s" % failure,
                logLevel=logging.CRITICAL)
        self.parent.stopService()

    def triggerCrawl(self, jobs):
        """
        Instantiates a new spider and triggers it to fetch the given resources.

        @param: jobs: List of jobs with URLs to fetch (must not be empty)
        @rtype: Deferred
        """
        assert(len(jobs) > 0)
        for job in jobs:
            self.pendingRequests[job.url] = ('job', job)
            try:
                req = self.spider.make_request_from_job(job)
            except ValueError as e:
                log.msg("Failed parsing URL of job %d: %s - skipping job" % (
                    job.id, repr(job.url)),
                    logLevel=logging.CRITICAL)
                continue
            self.engine.crawl(req, self.spider)

    def triggerSingleUrlFetch(self, url, task_uuid):
        self.pendingRequests[url] = ('task', task_uuid)
        req = self.spider.make_requests_from_url(url)
        self.engine.crawl(req, self.spider)

    def registerNewDocumentCallback(self, cb, *args, **kwargs):
        assert(self.newDocCallback is None)
        self.newDocCallback = (cb, args, kwargs)

    def gotResponse(self, response):
        originalUrl = response.url
        if 'redirect_urls' in response.request.meta:
            originalUrl = response.request.meta['redirect_urls'][0]
        # Assuming Scrapy doesn't mangle the original URL requested,
        # this should always get us the original job that started the
        # request. Ideally, we could pass it along with the original
        # request, but unfortunately scrapy doesn't offer any
        # arguments for their callbacks.
        try:
            kind, info = self.pendingRequests.pop(originalUrl)
            if kind == 'job':
                job = info
                task_uuid = None
            elif kind == 'task':
                job = None
                task_uuid = info
            else:
                assert(False)
        except KeyError:
            log.msg("Got a response for an unknown URL.",
                    logLevel=logging.CRITICAL, system="crawler")
            return

        contentType = response.headers.get('Content-Type')
        if contentType and ';' in contentType:
            parts = [x.strip() for x in contentType.split(';')]
            mediaType = parts[0]
            mediaParams = {}
            for param in parts[1:]:
                if '=' in param:
                    key, value = param.split('=', 2)
                    mediaParams[key] = value
                else:
                    log.msg("Content type parameter violates HTTP spec: '%s'"
                            % repr(contentType),
                            logLevel=logging.WARNING, system="crawler")
        elif contentType:
            mediaType = contentType.strip()
            mediaParams = {}
        else:
            mediaType = 'application/octet-stream';
            mediaParams = {}

        # Scrapy (as of 0.24) is not capable of streaming to a file.
        # So we collect it all in memory before writing it to the
        # disk. Even worse, in case of a cache hit, we read from the
        # cache only to write the same data back to a temporary
        # file. Yuck!
        with tempfile.NamedTemporaryFile(delete=False) as f:
            f.write(response.body)
            tmpFileName = f.name
            f.close()
        document = RetrievedDocument(kind,
                                     job,
                                     task_uuid,
                                     tmpFileName=tmpFileName,
                                     mediaType=mediaType,
                                     mediaParams=mediaParams,
                                     date=response.headers.get('Date'),
                                     entityTag=response.headers.get('ETag'))
        d = self.triggerDocumentCallback(document)
        d.addBoth(self.cleanupTempFile, tmpFileName)

    def triggerDocumentCallback(self, document):
        cb, args, kwargs = self.newDocCallback
        d = cb(document, *args, **kwargs)
        assert(isinstance(d, defer.Deferred))
        # catch any kind of errors - the callback needs to handle them
        d.addErrback(self.crashStop, "document callback")
        return d
