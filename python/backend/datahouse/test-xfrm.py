#!/usr/bin/env python

from __future__ import print_function

import re
import sys
import optparse
import os
import tempfile
import xml.sax
import json
from lxml import html
from collections import deque
from io import StringIO

from twisted.internet import reactor
from twisted.python import log

from datahouse.monitoor import PeriodicJob, RetrievedDocument, xfrms

def completed((mustAbort, errors, warnings, outData, mimeType)):
    if mustAbort:
        print("FATAL: conversion failed")
    if len(warnings) > 0:
        print("Warnings:")
        print("\n".join(warnings))
    if len(errors) > 0:
        print("Errors:")
        print("\n".join(errors))
    print("===== OUTPUT ======")
    print(outData)
    reactor.stop()

def main():
    p = optparse.OptionParser('%prog <options> [xfrm commands] [url]')
    p.add_option("-a", "--args",
                     action="store", type="string", dest="xfrm_args_str",
                     help="transformation arguments")

    (options, (xfrm_commands, url)) = p.parse_args()

    try:
        # Python 2.6 / 2.7
        import urllib2
        response = urllib2.urlopen(url)
        data = response.read()
    except ImportError:
        # Python 3
        import urllib.request
        req = urllib.request.Request(url)
        with urllib.request.urlopen(req) as response:
            data = response.read()

    fd, fn = tempfile.mkstemp()
    os.write(fd, data)
    os.close(fd)

    xfrm_args = json.loads(options.xfrm_args_str or '{}')
    pseudoJob = PeriodicJob({'job_id': -1, 'url': url,
                                 'min_check_interval': 3600})
    document = RetrievedDocument('kind', pseudoJob, 'non-uuid', fn,
                                     mediaType='text/html')

    d = xfrms.planXfrms(document, xfrm_commands, xfrm_args)
    d.addBoth(completed)
    reactor.run()

    os.unlink(fn)

if __name__ == "__main__":
    main()
