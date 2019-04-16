# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
import random
from twisted.internet import defer, endpoints, protocol, reactor
from twisted.trial import unittest
from twisted.web import resource, server

from datahouse.monitoor.scheduler import PeriodicJob
from datahouse.monitoor.app import CrawlerService

TIMEOUT = 5.0

class SimpleResource(resource.Resource):
    isLeaf = True
    def render_GET(self, request):
        request.setHeader('Content-Type', 'text/html; charset=utf-8')
        return "<html><body><h1>Hello, World!</h1></body></html>"

class EmptyResource(resource.Resource):
    isLeaf = True
    def render_GET(self, request):
        return ""

class NotFoundResource(resource.Resource):
    isLeaf = True
    def render_GET(self, request):
        request.setResponseCode(404)
        return "<html><body>Infamous error 404: Not found.</body></html>"

class RedirectedResource(resource.Resource):
    isLeaf = False
    def getChild(self, name, request):
        if name == '':
            return self
        elif name == 'next':
            return SimpleResource()
        else:
            assert(False)

    def render_GET(self, request):
        request.setResponseCode(301)
        request.setHeader('Location', '/next')
        return "<html><body>Error 301: Moved Permanently.</body></html>"

class InfiniteRedirectionResource(resource.Resource):
    isLeaf = True
    def render_GET(self, request):
        request.setResponseCode(301)
        request.setHeader('Location', '/')
        return "<html><body>Error 301: Moved Permanently.</body></html>"

class PaymentRequiredResource(resource.Resource):
    def render_GET(self, request):
        request.setResponseCode(402)
        return "<html><body>Please swipe your credit card.</body></html>"

# A partial HTTP header, intentionally interrupted
PARTIAL_ANSWER = """HTTP/1.1 200 OK
Server: NastyMockServer/-1 (Garnix)
Content-Length: 42
Content-Language: en
Connection: close
Content-Ty"""

class InterruptedResponseProtocol(protocol.Protocol):
    def dataReceived(self, data):
        if data[:3] in ('GET', 'HEA', 'POS'):
            self.transport.write(PARTIAL_ANSWER)
            self.transport.loseConnection()

class InterruptedResponseFactory(protocol.ServerFactory):
    protocol = InterruptedResponseProtocol


class NeverEndingProtocol(protocol.Protocol):
    def dataReceived(self, data):
        if data[:3] in ('GET', 'HEA', 'POS'):
            self.transport.write(PARTIAL_ANSWER)
            # not much more to do, keep the request open
class InfiniteResponseFactory(protocol.ServerFactory):
    protocol = NeverEndingProtocol

class Timeout(Exception):
    pass

class ProcessTestCase(unittest.TestCase):
    def setUp(self):
        self.portNumber = random.randint(10000, 65535)
        self.endpoint = endpoints.TCP4ServerEndpoint(reactor,
                                                     self.portNumber)
        self.port = None

        self.documents = []
        self.responseTimer = None

        self.crawler = CrawlerService()
        self.crawler.registerNewDocumentCallback(self.retrievedDocument)
        self.crawler.startService()

    def tearDown(self):
        self.crawler.stopService()
        if self.port is not None:
            self.port.stopListening()

    @defer.inlineCallbacks
    def serveProtocol(self, proto):
        self.port = yield self.endpoint.listen(proto)

    @defer.inlineCallbacks
    def serveAndTriggerFetch(self, proto):
        yield self.serveProtocol(proto)
        job = PeriodicJob({'job_id': 42,
                            'url': 'http://localhost:%d/' % self.portNumber,
                            'min_check_interval': 10})
        self.crawler.triggerCrawl([job])
        yield self.awaitResponse()

    def retrievedDocument(self, document):
        self.documents.append(document)
        assert(not self.responseTimer.called)
        self.responseTimer.cancel()
        if self.responseDeferred is not None:
            d, self.responseDeferred = self.responseDeferred, None
            d.callback(None)
        return defer.succeed(None)

    def awaitResponse(self):
        self.responseDeferred = defer.Deferred()
        self.responseTimer = reactor.callLater(TIMEOUT, self.timeout)
        return self.responseDeferred

    def timeout(self):
        if self.responseDeferred is not None \
                and not self.responseDeferred.called:
            self.responseDeferred.errback(
                Timeout("No answer within %d seconds." % TIMEOUT))
            self.responseTimer = None

    @defer.inlineCallbacks
    def test_fetch_simple(self):
        r = SimpleResource()
        proto = server.Site(r)
        yield self.serveAndTriggerFetch(proto)

    @defer.inlineCallbacks
    def test_fetch_empty(self):
        r = EmptyResource()
        proto = server.Site(r)
        yield self.serveAndTriggerFetch(proto)

    @defer.inlineCallbacks
    def test_fetch_not_found(self):
        r = NotFoundResource()
        proto = server.Site(r)
        yield self.serveAndTriggerFetch(proto)
    test_fetch_not_found.skip = 'not properly implemented, yet'

    @defer.inlineCallbacks
    def test_empty_strange_error(self):
        r = PaymentRequiredResource()
        proto = server.Site(r)
        yield self.serveAndTriggerFetch(proto)
    test_empty_strange_error.skip = 'not properly implemented, yet'

    def test_interrupted_response(self):
        proto = InterruptedResponseFactory()
        d = self.serveAndTriggerFetch(proto)
        self.assertFailure(d, [Timeout])
        # FIXME: not getting an answer within 5 seconds is no
        # indication for proper handling of unresponsive servers...
    test_interrupted_response.skip = 'not properly implemented, yet'

    def test_infinite_response(self):
        proto = InfiniteResponseFactory()
        d = self.serveAndTriggerFetch(proto)
        self.assertFailure(d, [Timeout])
        # FIXME: not getting an answer within 5 seconds is no
        # indication for proper handling of unresponsive servers...
    test_infinite_response.skip = 'not properly implemented, yet'

    def test_simple_redirection(self):
        r = RedirectedResource()
        proto = server.Site(r)
        return self.serveAndTriggerFetch(proto)

    def test_infinite_redirection(self):
        r = InfiniteRedirectionResource()
        proto = server.Site(r)
        return self.serveAndTriggerFetch(proto)
    test_infinite_redirection.skip = 'not properly implemented, yet'
