from Monitor import *

import scrapy

class MonitorSpider(scrapy.Spider):
    name = "monitor"
    start_urls = URLHandler.getList()

    def parse(self, response):
        s = MonitoredSite(response)
        s.process()






