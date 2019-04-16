# -*- coding: utf-8 -*-
# Copyright (c) 2016, Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
import random
from twisted.internet import defer, endpoints, protocol, reactor
from twisted.trial import unittest
from twisted.web import resource, server

from datahouse.monitoor.scheduler import Scheduler

class KeywordListGrammarTestCase(unittest.TestCase):
    def testSimpleKeyword(self):
        x = Scheduler.parseKeywordList(u'zug')
        self.assertEquals(x, [(u'zug', False)])

    def testNegatedKeyword(self):
        x = Scheduler.parseKeywordList(u'-bahn')
        self.assertEquals(x, [(u'bahn', True)])

    def testKeywordWithDash(self):
        x = Scheduler.parseKeywordList(u's-bahn')
        self.assertEquals(x, [(u's-bahn', False)])

    def testQuotedKeyword(self):
        x = Scheduler.parseKeywordList(u'zug "s-bahn"')
        self.assertEquals(x, [
            (u'zug', False),
            (u'"s-bahn"', False)
        ])
