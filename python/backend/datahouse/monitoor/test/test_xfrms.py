# -*- coding: utf-8 -*-
#
# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os

from twisted.internet import defer
from twisted.trial import unittest

from datahouse.monitoor import RetrievedDocument

from datahouse.monitoor import xfrms

class ProcessTestCase(unittest.TestCase):

    TEST_LINE = "Lorem ipsum idolor\n"
    TEST_LINE_SHA256 = \
      "37c1db945ad96666be55bb4fb983c062892662cef094b52b61b63f43c8ad2242"

    TEST_XML_DATA = """<?xml version="1.0" encoding="UTF-8"?>
<ns:example version="1.0" xmlns:ns="example/config/xml">
</ns:example>
"""

    TEST_HTML_DATA = """<html>
<head><title>bad example</title></head>
<body>
  <h1>Hello, World!</h1>
  <div class="first">
    <p>some test</p>
  </div>
</body>
</html>
"""

    TEST_RDF_DATA = """<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://my.netscape.com/rdf/simple/0.9/">
  <channel>
    <title>Test Channel</title>
    <link>https://www.example.com/test.html</link>
    <description>Just a test</description>
  </channel>

  <item>
    <title>First Entry</title>
    <link>https://www.example.com/first</link>
    <description>
      Not quite a real English sentence this first entry to prevent the
      usual lorem ipsum crap sports.
    </description>
    <guid isPermaLink="true">https://www.example.com/first</guid>
  </item>

  <item>
    <title>Second Entry</title>
    <link>https://www.example.com/second</link>
    <description>
      To confuse another entry you just more.
    </description>
    <guid isPermaLink="true">https://www.example.com/second</guid>
  </item>
</rdf:RDF>
"""

    EXP_RDF_MARKDOWN_RESULT = """## First Entry

[Link](https://www.example.com/first)

Not quite a real English sentence this first entry to prevent the usual
lorem ipsum crap sports.

## Second Entry

[Link](https://www.example.com/second)

To confuse another entry you just more.

"""

    EXP_RDF_MARKDOWN_SPLIT_RESULT = """====================548487216==
Content-Type: text/markdown
Content-Id: https://www.example.com/first

## First Entry

[Link](https://www.example.com/first)

Not quite a real English sentence this first entry to prevent the usual
lorem ipsum crap sports.


====================548487216==
Content-Type: text/markdown
Content-Id: https://www.example.com/second

## Second Entry

[Link](https://www.example.com/second)

To confuse another entry you just more.


====================548487216==
"""

    TEST_EXAMPLE_TEXT_OUTPUT = '****** Hello, World! ******\nsome test\n'
    TEST_EXAMPLE_MARKDOWN_OUTPUT = '# Hello, World!\n\nsome test\n\n'

    TEST_DIV_RESTRICTED_MARKDOWN_OUTPUT = 'some test\n\n'

    TEST_UTF8_BOM = "\xEF\xBB\xBF"
    TEST_UTF16LE_BOM = "\xFF\xFE"

    def setUp(self):
        self.maxDiff = None
        with open('fileA.txt', 'w') as f:
            f.write(self.TEST_LINE)
        with open('fileB.xml', 'w') as f:
            f.write(self.TEST_XML_DATA)
        with open('fileC.html', 'w') as f:
            f.write(self.TEST_HTML_DATA)
        with open('fileD.rdf', 'w') as f:
            f.write(self.TEST_RDF_DATA)

    def tearDown(self):
        for fn in ('fileA.txt', 'fileB.xml', 'fileC.html', 'fileD.rdf'):
            os.unlink(fn)

    @defer.inlineCallbacks
    def test_file_hash(self):
        hex_hash = (yield xfrms.calcFileHash('fileA.txt')).encode('hex')
        self.assertEqual(hex_hash, self.TEST_LINE_SHA256)

    @defer.inlineCallbacks
    def test_str_hash(self):
        hex_hash = (yield xfrms.calcStrHash(self.TEST_LINE)).encode('hex')
        self.assertEqual(hex_hash, self.TEST_LINE_SHA256)

    @defer.inlineCallbacks
    def tryPlanXfrms(self, input, exp_output, exp_mime_type):
        mustAbort, errors, warnings, out_data, mime_type \
          = yield xfrms.planXfrms(*input)
        self.assertEqual("; ".join(errors), "")
        self.assertEqual("; ".join(warnings), "")
        self.assertMultiLineEqual(exp_output, out_data)
        self.assertEqual(mustAbort, False)

    @defer.inlineCallbacks
    def test_planning_simple(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileC.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document, "html2text", {}),
                                self.TEST_EXAMPLE_TEXT_OUTPUT,
                                'text/text')

    @defer.inlineCallbacks
    def test_planning_markdown(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileC.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document, "html2markdown", {}),
                                self.TEST_EXAMPLE_MARKDOWN_OUTPUT,
                                'text/markdown')

    @defer.inlineCallbacks
    def test_planning_simple_xpath(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileC.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document,
                                 "xpath|html2markdown",
                                 {'xpath': '//body'}),
                                self.TEST_EXAMPLE_MARKDOWN_OUTPUT,
                                'text/markdown')

    @defer.inlineCallbacks
    def test_planning_simple_single_quoted_xpath(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileC.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document,
                                 "xpath|html2markdown",
                                 {'xpath': "div[@class='first']"}),
                                self.TEST_DIV_RESTRICTED_MARKDOWN_OUTPUT,
                                'text/markdown')

    @defer.inlineCallbacks
    def test_planning_simple_double_quoted_xpath(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileC.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document,
                                 "xpath|html2markdown",
                                 {'xpath': 'div[@class="first"]'}),
                                self.TEST_DIV_RESTRICTED_MARKDOWN_OUTPUT,
                                'text/markdown')

    @defer.inlineCallbacks
    def test_planning_bom_prefixed_xpath(self):
        with open('fileX.html', 'w') as f:
            f.write(self.TEST_UTF8_BOM)
            f.write(self.TEST_HTML_DATA)

        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileX.html',
                                     mediaType='text/html',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Wed, 09 Sep 2015 11:21:59 GMT',
                                     entityTag=None)
        yield self.tryPlanXfrms((document,
                                 "xpath|html2markdown",
                                 {'xpath': '//body'}),
                                self.TEST_EXAMPLE_MARKDOWN_OUTPUT,
                                'text/markdown')
        os.unlink('fileX.html')

    @defer.inlineCallbacks
    def test_simple_rdf_document(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileD.rdf',
                                     mediaType='application/pdf+xml',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Fri, 07 Apr 2017 12:52:01 UTC',
                                     entityTag=None)
        yield self.tryPlanXfrms((document, "rss2markdown", {}),
                                self.EXP_RDF_MARKDOWN_RESULT,
                                'text/markdown')

    @defer.inlineCallbacks
    def test_simple_multipart_rdf(self):
        document = RetrievedDocument('job', None, None,
                                     tmpFileName='fileD.rdf',
                                     mediaType='application/pdf+xml',
                                     mediaParams={'charset': 'utf-8'},
                                     date='Fri, 07 Apr 2017 12:52:01 UTC',
                                     entityTag=None)
        yield self.tryPlanXfrms((document, "rss2markdown-split", {}),
                                self.EXP_RDF_MARKDOWN_SPLIT_RESULT,
                                'multipart/mixed')
