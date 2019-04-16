# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

import os
from twisted.internet import defer
from twisted.trial import unittest

from datahouse.monitoor.process import Process, InvalidUse, \
     ProcessTimedOutError

class ProcessTestCase(unittest.TestCase):

    TEST_LINE = "Lorem ipsum idolor\n"
    TEST_LINE_SHA256 = \
      "37c1db945ad96666be55bb4fb983c062892662cef094b52b61b63f43c8ad2242"

    def setUp(self):
        with open('fileA.txt', 'w') as f:
            f.write(self.TEST_LINE)

    def tearDown(self):
        os.unlink('fileA.txt')

    @defer.inlineCallbacks
    def test_captured_output(self):
        proc = Process(['/usr/bin/sha256sum', 'fileA.txt'])
        exitCode = yield proc.run()
        self.assertEqual(exitCode, 0)
        self.assertEqual(proc.getOutStr().strip(),
                         self.TEST_LINE_SHA256 + "  fileA.txt")
        self.assertEqual(proc.getErrStr().strip(), '')

    @defer.inlineCallbacks
    def test_input_pipe(self):
        proc = Process(['/usr/bin/sha256sum'],
                       input=self.TEST_LINE)
        exitCode = yield proc.run()
        self.assertEqual(exitCode, 0)
        self.assertEqual(proc.getOutStr().strip(),
                         self.TEST_LINE_SHA256 + "  -")
        self.assertEqual(proc.getErrStr().strip(), '')

    @defer.inlineCallbacks
    def test_file_output(self):
        with open('fileB.txt', 'wb+') as f:
            proc = Process(['/bin/cat'],
                        input=self.TEST_LINE, output=f)
            exitCode = yield proc.run()
        self.assertRaises(InvalidUse, proc.getOutStr)
        self.assertEqual(proc.getErrStr().strip(), '')
        self.assertEqual(exitCode, 0)
        with open('fileB.txt', 'rb') as f:
            self.assertEqual(f.read(), self.TEST_LINE)
        os.unlink('fileB.txt')

    def test_timeout(self):
        proc = Process(['/bin/sleep', '15'], input="", timeout=0.1)
        d = proc.run()
        self.failUnlessFailure(d, ProcessTimedOutError)
        return d
