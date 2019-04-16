#!/usr/bin/env python

# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

from __future__ import print_function

import optparse
import logging
import sys

from HTMLParser import HTMLParser

CHUNK_SIZE = 65536

def main():
    # To enable debug messages, uncomment this:
    #logging.basicConfig(level=logging.DEBUG)

    p = optparse.OptionParser('%prog [file [encoding]]')
    (options, args) = p.parse_args()

    if len(args) > 0:
        filename = args[0]
        if len(args) > 1:
            p.error('Too many arguments')

        assert(not filename.startswith('http://'))
        assert(not filename.startswith('https://'))
        if filename == '-':
            f = sys.stdin
        else:
            f = open(filename, 'rb')
    else:
        f = sys.stdin

    in_cdata = False
    eof = False
    rest = u""
    raw_rest = ""
    htmlParser = HTMLParser()
    while not eof or len(rest) > 0 or len(raw_rest) > 0:
        chunk = rest
        while not eof and len(chunk) < CHUNK_SIZE:
            data_read = f.read(CHUNK_SIZE)
            if data_read:
                data_read = raw_rest + data_read
                end_idx = len(data_read)
                while True:
                    try:
                        decoded_data = data_read[:end_idx].decode('utf-8')
                    except UnicodeDecodeError:
                        end_idx -= 1
                        continue
                    break
                chunk = chunk + decoded_data
                raw_rest = data_read[end_idx:]
            else:
                eof = True

        logging.debug("chunk: %s" % repr(chunk))
        if in_cdata:
            cdata_end_idx = chunk.find(']]>')
            if cdata_end_idx == -1:
                emit = chunk[:-3]
                rest = chunk[-3:]
            else:
                cdata_end_idx += 3
                emit = chunk[:cdata_end_idx]
                rest = chunk[cdata_end_idx:]
                in_cdata = False
        else:
            cdata_start_idx = chunk.find('<![CDATA[')
            if cdata_start_idx == -1:
                if eof:
                    emit = chunk
                    rest = u""
                else:
                    # check the last 10 bytes for an ampersand or a less than
                    # character.
                    l = len(chunk)
                    try:
                        last_amp_idx = chunk.rindex('&', l - 10, l)
                    except ValueError:
                        last_amp_idx = l
                    try:
                        last_lt_idx = chunk.rindex('<', l - 10, l)
                    except ValueError:
                        last_lt_idx = l
                    last_interesting_idx = min(last_amp_idx, last_lt_idx)
                    if last_interesting_idx == l or eof:
                        emit = chunk
                        rest = ""
                    else:
                        # in case of an ampersand within the last
                        # 10 bytes, emit only the chunk before the
                        # ampersand and everything from there on
                        # in the 'rest' to ensure we are not splitting
                        # chunks in the middle of an HTML entity.
                        emit = chunk[:last_interesting_idx]
                        rest = chunk[last_interesting_idx:]
            else:
                emit = chunk[:cdata_start_idx]
                rest = chunk[cdata_start_idx:]
                in_cdata = True
            emit = htmlParser.unescape(emit)

        logging.debug("in_cdata: %s, eof: %s" % (repr(in_cdata), repr(eof)))
        logging.debug("emit: %s" % repr(emit))
        logging.debug("rest: %s" % repr(rest))

        sys.stdout.write(emit.encode('utf-8'))

if __name__ == "__main__":
    main()
