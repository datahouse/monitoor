# -*- coding: utf-8 -*-
# Copyright (c) 2016, Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
import random
#from twisted.internet import defer, endpoints, protocol, reactor
from twisted.trial import unittest
#from twisted.web import resource, server

from datahouse.monitoor.scheduler import Scheduler

class NeedleTestCase(unittest.TestCase):
    TEST_TEXT = u'The alpha yoghurt is hunting the containing dressing.'

    def testSingleLineMatch(self):
        section = {'add': [self.TEST_TEXT]}
        nhits, mpos = Scheduler.searchNeedleInSection('hunting', section)
        self.assertEquals(nhits, 1)
        self.assertEquals(mpos, [[21]])

    def testSingleLineMismatch(self):
        section = {'add': [self.TEST_TEXT]}
        nhits, mpos = Scheduler.searchNeedleInSection('beta', section)
        self.assertEquals(nhits, 0)
        self.assertEquals(mpos, [[]])

    def testMultiLineMatches(self):
        section = {'add': [self.TEST_TEXT, self.TEST_TEXT, 'alpha alpha']}
        nhits, mpos = Scheduler.searchNeedleInSection('alpha', section)
        self.assertEquals(nhits, 4)
        self.assertEquals(mpos, [[4], [4], [0, 6]])

    def testRemovalOnlySection(self):
        section = {'del': ['text removed']}
        nhits, mpos = Scheduler.searchNeedleInSection('alpha', section)
        self.assertEquals(nhits, 0)
        self.assertEquals(mpos, None)

    def testUnicodeMatch(self):
        section = {'add': [u'Erdmännchen']}
        nhits, mpos = Scheduler.searchNeedleInSection(u'Erdmännchen', section)
        self.assertEquals(nhits, 1)
        self.assertEquals(mpos, [[0]])

    def testUnicodeMatchCaseInsensitive(self):
        section = {'add': [u'Erdmännchen']}
        nhits, mpos = Scheduler.searchNeedleInSection(u'erdmännchen', section)
        self.assertEquals(nhits, 1)
        self.assertEquals(mpos, [[0]])

    def testSpecalCharacterKeywords(self):
        section = {'add': [u'Our R&D departement']}
        nhits, mpos = Scheduler.searchNeedleInSection(u'R&D', section)
        self.assertEquals(nhits, 1)
        self.assertEquals(mpos, [[4]])
