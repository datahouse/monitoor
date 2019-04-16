# -*- coding: utf-8 -*-
# Copyright (c) 2016, Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
import random
from twisted.internet import defer, endpoints, protocol, reactor
from twisted.trial import unittest
from twisted.web import resource, server

from datahouse.monitoor.scheduler import Scheduler

class KeywordsTestCase(unittest.TestCase):
    TEST_TEXT = u'The alpha yoghurt is hunting the containing dressing.'

    def tryRun(self, keyword_lists, exp_matches, exp_positions):
        sections = [{'add': [self.TEST_TEXT]}]
        matches, match_positions = Scheduler.searchForKeywords(
            keyword_lists, sections)
        self.assertEquals(matches, exp_matches)
        section_matches = match_positions[0] if 0 in match_positions else {}
        self.assertEquals(section_matches, exp_positions)

    def testSingleKeyword(self):
        self.tryRun([u'alpha'], ['alpha'], {'alpha': [[4]]})

    def testTwoOredKeywordsKeyword(self):
        self.tryRun([u'alpha', u'beta'], ['alpha'], {'alpha': [[4]]})

    def testMatchOnTwoCombinedKeywordsKeyword(self):
        self.tryRun([u'alpha dressing'],
                    ['alpha dressing'],
                    {'alpha': [[4]], 'dressing': [[44]]})

    def testMatchOnTwoCommaSeparatedKeywordsKeyword(self):
        self.tryRun([u'alpha, dressing'],
                    ['alpha, dressing'],
                    {'alpha': [[4]], 'dressing': [[44]]})

    def testMatchOnTwoCommaSemicolonKeywordsKeyword(self):
        self.tryRun([u'alpha; dressing'],
                    ['alpha; dressing'],
                    {'alpha': [[4]], 'dressing': [[44]]})

    def testMatchOnTwoCombinedKeywordsKeyword(self):
        self.tryRun([u'alpha beta'], [], {})

    def testKeywordSequenceMatch(self):
        self.tryRun([u'"alpha yoghurt"'],
                    ['"alpha yoghurt"'], {'"alpha yoghurt"': [[4]]})

    def testKeywordSequenceMismatch(self):
        self.tryRun([u'"alpha dressing"'], [], {})

    def testKeywordNegationMatch(self):
        self.tryRun([u'alpha -beta'], ['alpha -beta'], {'alpha': [[4]]})

    def testKeywordNegationMismatch(self):
        self.tryRun([u'beta -alpha'], [], {})

    def testDanglingQuotes(self):
        # if we miss a quote, simply assume a terminal one
        self.tryRun([u'"alpha yoghurt'],
                    ['"alpha yoghurt'],
                    {'"alpha yoghurt"': [[4]]})

    def testComplexConditionsMatch(self):
        self.tryRun([u'alpha -yoghurt', u'dressing'],
                    ['dressing'],
                    {'dressing': [[44]]})

    def testComplexConditionsMismatch(self):
        self.tryRun([u'alpha -yoghurt', u'beta'], [],
                    {})

    def testKeywordWithUmlauts(self):
        self.tryRun([u'ümlauts'], [], {})

    def testMatchingUmlauts(self):
        sections = [{'add': [u'Und jetzt der Auftritt des euphorischen' +
                             u'Erdmännchens, dessen Mütze nur knapp über' +
                             u'die Nasenspitze heraus ragt.']}]
        matches, sections = Scheduler.searchForKeywords(
            [u'Erdmännchens'], sections)
        self.assertEquals(matches, [u'Erdmännchens'])

    def testSpecialCharsSingleKeyword(self):
        sections = [{'add': [u'Our R&D departement']}]
        matches, match_positions = Scheduler.searchForKeywords(
            [u'R&D'], sections)
        self.assertEquals(matches, [u'R&D'])
        self.assertEquals(match_positions, {0: {'r&d': [[4]]}})

    def testSpecialCharsMultipleKeywords(self):
        sections = [{'add': [u'Our R & D departement']}]
        matches, match_positions = Scheduler.searchForKeywords(
            [u'R & D'], sections)
        self.assertEquals(matches, [u'R & D'])
        self.assertEquals(match_positions,
                          {0: {u'r': [[2, 4, 14]],
                               u'&': [[6]],
                               u'd': [[8, 10]]}})

    def testNeedleAtStartOfCompoundWord(self):
        sections = [{'add': [u'soll der Zugang zu Land und Hof...']}]
        matches, match_positions = Scheduler.searchForKeywords(
            [u'zug'], sections)
        self.assertEquals(matches, [u'zug'])
        self.assertEquals(match_positions, {0: {u'zug': [[9]]}})

    def testNeedleAtEndOfCompoundWord(self):
        sections = [{
            'add': [u'..."gilt weltweit als Vorbild im Justizvollzug.']
        }]
        matches, match_positions = Scheduler.searchForKeywords(
            [u'zug'], sections)
        self.assertEquals(matches, [u'zug'])
        self.assertEquals(match_positions, {0: {u'zug': [[43]]}})

    def testNeedleAtMiddleOfCompoundWord(self):
        sections = [{'add': [u'Justizvollzugsanstalt']}]
        matches, match_positions = Scheduler.searchForKeywords(
            [u'zug'], sections)
        self.assertEquals(matches, [u'zug'])
        self.assertEquals(match_positions, {0: {u'zug': [[10]]}})

    def testMatchOneButNotTheOther(self):
        """ When matching only one out of the list of keywords, do not mark
            keywords from other entries (as they are and-connected and might
            not have matched).
        """
        self.tryRun([u'beta dressing', u'hunt'],
                    [u'hunt'],
                    {u'hunt': [[21]]})
