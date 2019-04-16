# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
Spider class(es) for the monitoor project.
"""

import scrapy
from scrapy.spider import Spider
from scrapy import log

class Spider(Spider):
    """A simple and dumbed-down spider, which fetches just exactly one single
    resource.
    """

    name = 'datahouse-mon-backend'

    def parse(self, response):
        """
        Callback for fetched resources.

        @returns: empty list, meaning this spider doesn't yield any items nor
        does it continue to crawl any further URLs.
        """
        self.crawler.gotResponse(response)
        return []

    def make_request_from_job(self, job):
        return self.make_requests_from_url(job.url)
