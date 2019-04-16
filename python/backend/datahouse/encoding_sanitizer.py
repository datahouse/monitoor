#!/usr/bin/env python

# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

# A helper tool which does its best to convert its input to proper
# UTF-8. An encoding may be specified. If omitted, chardet is used to
# guess it.

from __future__ import print_function

import re
import logging
import sys
import html2text
import optparse
import chardet

from HTMLParser import HTMLParser

XML_CHARSET_REGEX = """<\??(?:xml|html)[^>]*encoding=\"([^\"]*)\"\??>"""
HTML_CHARSET_REGEX = 'meta\s+.*charset=\"([^"]+)\"'

INIT_CHUNK_SIZE = 1024 * 1024
CHUNK_SIZE = 1024 * 8

def tryDecode(chunk, *args, **kwargs):
    for i in range(4):
        try:
            if i == 0:
                udata = chunk.decode(*args, **kwargs)
                rest = ''
            else:
                udata = chunk[:-i].decode(*args, **kwargs)
                rest = chunk[-i:]
            return udata, rest
        except UnicodeDecodeError:
            pass
    # every attempt failed, so far
    logging.debug("failed trying with %s %s\n" % (repr(args), repr(kwargs)))
    return None, ''

def main():
    # To enable debug messages, uncomment this:
    # logging.basicConfig(level=logging.DEBUG)

    p = optparse.OptionParser('%prog [file [encoding]]')
    (options, args) = p.parse_args()

    encoding = None
    if len(args) > 0:
        filename = args[0]
        if len(args) == 2:
            encoding = args[1]
        if len(args) > 2:
            p.error('Too many arguments')

        assert(not filename.startswith('http://'))
        assert(not filename.startswith('https://'))
        if filename == '-':
            f = sys.stdin
        else:
            f = open(filename, 'rb')
    else:
        f = sys.stdin

    # For performance reasons, we do not load the entire document, but
    # only the first 1 MiB of data. This might split an UTF-8 symbol
    # in the middle. However, the tryDecode method takes care of that.
    data = f.read(INIT_CHUNK_SIZE)
    if len(data) == 0:
        sys.exit(0)

    udata = None
    rest = ''
    if encoding is not None:
        udata, rest = tryDecode(data, encoding)

    if udata is None:
        logging.debug("trying to determine from header")
        # The given encoding didn't work. Here all the fun starts.
        # Check for content-type in the first 1000 bytes of data.
        header = data[:1000]
        rest = data[1000:]
        header_encoding = None
        meta_encoding = None
        m = re.search(XML_CHARSET_REGEX, header)
        if m:
            header_encoding = m.group(1)
            print("start: %s" % m.start(1), file=sys.stderr)
            header = header[:m.start(1)] + "utf-8" + header[m.end(1):]

        m = re.search(HTML_CHARSET_REGEX, header)
        if m:
            meta_encoding = m.group(1)
            print("start: %s" % m.start(1), file=sys.stderr)
            header = header[:m.start(1)] + "utf-8" + header[m.end(1):]

        if header_encoding is not None:
            encoding = header_encoding
        elif meta_encoding is not None:
            encoding = meta_encoding

        if encoding is not None:
            udata, rest = tryDecode(header + rest, encoding)

    # As a very last resort, fall back to using automatic
    # chardet. Note that chardet is very well capable of detecting
    # UTF-8 even if the last symbol is corrupt.
    if udata is None:
        x = chardet.detect(data)
        res = chardet.detect(data)
        logging.debug("trying chardet: result: %s" % repr(res))
        encoding = res['encoding']
        if res['encoding']:
            udata, rest = tryDecode(data, encoding)

    # As a very last resort, fall back to cp1252 (a common browser's
    # default encoding).
    if udata is None:
        logging.debug("fallback to cp1252")
        encoding = 'cp1252'
        udata, rest = tryDecode(data, encoding)

    logging.debug("decided on encoding %s" % encoding)

    # If present, strip the initial byte order mark. It's mostly
    # non-sensical for UTF-8, anyways.
    if udata is not None and len(udata) >= 0 and udata[0] == u'\uFEFF':
        udata = udata[1:]

    htmlParser = HTMLParser()

    # Emit that first chunk to stdout.
    sys.stdout.write(htmlParser.unescape(udata).encode('utf-8'))

    # Then loop over the rest in chunks and convert, as appropriate.
    while True:
        data_read = f.read(CHUNK_SIZE)
        if not data_read:
            if len(rest) > 0:
                data = rest
            else:
                break
        else:
            data = rest + data_read

        # Convert chunk, replacing errors, here.
        udata, rest = tryDecode(data, encoding, errors='replace')
        sys.stdout.write(htmlParser.unescape(udata).encode('utf-8'))

if __name__ == "__main__":
    main()
