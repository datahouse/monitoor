#!/usr/bin/env python

from __future__ import print_function

import re
import sys
import optparse
import xml.sax
from lxml import html
from collections import deque
from io import StringIO

try:
    # Python 2.6 and 2.7
    from HTMLParser import HTMLParser
except ImportError:
    # Python 3
    from html.parser import HTMLParser

htmlParser = HTMLParser()

class RssHandler(xml.sax.ContentHandler):
    def __init__(self, boundary=None):
        self.stack = []
        self.content = u''
        self.boundary = boundary
        if boundary:
            self.writeOut(boundary + '\n')
        self.resetCurrentItem()

    def resetCurrentItem(self):
        self.item = u''
        self.item_id = None

    def textifyXml(self, content):
        content = content.strip()
        if content.startswith('<![CDATA[') and contents.endswith(']]>'):
            content = content[9:-3]

        content = htmlParser.unescape(content)
        content = re.sub(r'<!--.*-->', ' ', content)
        content = re.sub(r'<[^<]+>', ' ', content)
        return htmlParser.unescape(content)

    def breakLines(self, content, maxCols=76):
        lines = []

        content = self.textifyXml(content)

        # Eliminate newlines and combine multiple spaces
        content = re.sub(u'\s+', u' ', content).strip()
        while len(content) > maxCols:
            off = 0
            idx = 0
            while True:
                last_idx = idx
                off = idx + 1
                idx = content.find(' ', off)
                if idx == -1:
                    break
                if idx > maxCols:
                    break
            lines.append(content[:last_idx])
            content = content[last_idx + 1:]
        lines.append(content)
        return u'\n'.join(lines)

    def writeOut(self, content):
        if sys.version_info[0] < 3:
            sys.stdout.write(content.encode('utf-8'))
        else:
            sys.stdout.write(content)

    def outputMarkdown(self, content):
        self.item += content

    def maybeEmitMultipartBoundary(self):
        if self.boundary:
            self.writeOut('\n' + self.boundary + '\n')

    def complainAboutUnknownContent(self, content):
        path = '/'.join(self.stack)
        if len(content) > 240:
            content = content[:240]
        msg = u"!! Unknown content at %s: %s\n" % (path, content)
        if sys.version_info[0] < 3:
            sys.stderr.write(msg.encode('utf-8'))
        else:
            sys.stderr.write(msg)

    def startElement(self, tag, attrs):
        tag = tag.lower()
        if ':' in tag:
            tag = tag[tag.find(':') + 1:]
        if tag == 'rss':
            pass
        else:
            self.stack.append(tag)

    def endElement(self, tag):
        self.outputCollectedData()

        tag = tag.lower()
        if tag in ('item', 'entry'):
            if self.boundary:
                self.writeOut('Content-Type: text/markdown\n')
                if self.item_id:
                    self.writeOut('Content-Id: ' + self.item_id + '\n')
                self.writeOut('\n')
            self.writeOut(self.item)
            self.maybeEmitMultipartBoundary()
            self.resetCurrentItem()
        if ':' in tag:
            tag = tag[tag.find(':') + 1:]
        if tag == 'rss':
            pass
        else:
            t = self.stack.pop()
            if t != tag:
                sys.stderr.write(u"Mismatching tags: %s vs %s" % (t, tag))

    def outputCollectedData(self):
        (content, self.content) = self.content.strip(), u''
        if len(content) == 0:
            return
        if len(self.stack) < 1:
            pass
        elif self.stack[0] in ('channel', 'feed', 'rdf'):
            if len(self.stack) < 2:
                pass
            elif self.stack[1] in ('channel'):
                pass
            elif self.stack[1] in ('title', 'subtitle'):
                # Main markdown title
                #self.outputMarkdown(u'# ' + content + u'\n\n')
                pass
            elif self.stack[1] == 'description':
                pass
                #self.outputMarkdown(self.breakLines(content) + u'\n\n')
            elif self.stack[1] in ('image', 'language', 'link', 'id',
                                   'logo', 'ttl', 'webmaster', 'feedflare',
                                   'generator', 'rating',
                                   'updatefrequency', 'updateperiod',
                                   'browserfriendly', 'category',
                                   'rsslink', 'docs', 'icon',
                                   'feedburnerhostname', 'managingeditor',
                                   'emailserviceid', 'feed_asset',
                                   'site'):
                # ignore
                pass
            elif self.stack[1] in ('lastbuilddate', 'updated'):
                #self.outputMarkdown(u"Last build date: %s\n\n" % content)
                pass
            elif self.stack[1] in ('pubdate'):
                # self.outputMarkdown(u"Publication date: %s\n\n" % content)
                pass
            elif self.stack[1] in ('copyright', 'rights'):
                #self.outputMarkdown(self.breakLines(content) + u'\n\n')
                pass
            elif self.stack[1] in ('author'):
                if len(self.stack) < 3:
                    pass
                elif self.stack[2] in ('name',):
                    self.outputMarkdown('Author: %s\n\n'
                        % self.textifyXml(content))
                elif self.stack[2] in ('uri',):
                    pass
                else:
                    path = '/'.join(self.stack)
                    self.complainAboutUnknownContent(content)
            elif self.stack[1] in ('item', 'entry'):
                if len(self.stack) < 3:
                    pass
                elif self.stack[2] in ('id', 'uid', 'guid', 'post-id'):
                    self.item_id = content.strip()
                elif self.stack[2] in ('title',):
                    self.outputMarkdown('## %s\n\n' % self.textifyXml(content))
                elif self.stack[2] in ('nl-catchline', 'nl-title'):
                    #self.outputMarkdown(u'### ' + content + u'\n\n')
                    pass
                elif self.stack[2] in ('description', 'summary'):
                    self.outputMarkdown(self.breakLines(content) + u'\n\n')
                elif self.stack[2] == 'author':
                    #self.outputMarkdown('Author: %s\n\n' % content)
                    pass
                elif self.stack[2] == 'creator':
                    #self.outputMarkdown('Creator: %s\n\n' % content)
                    pass
                elif self.stack[2] == 'source':
                    self.outputMarkdown('Source: %s\n\n'
                        % self.textifyXml(content))
                elif self.stack[2] == 'category':
                    #self.outputMarkdown('Category: %s\n\n' % content)
                    pass
                elif self.stack[2] in ('pubdate', 'published'):
                    #self.outputMarkdown('Publication date: %s\n\n' % content)
                    pass
                elif self.stack[2] in ('updated'):
                    #self.outputMarkdown('Last update: %s\n\n' % content)
                    pass
                elif self.stack[2] == 'link':
                    self.outputMarkdown('[Link](%s)\n\n'
                        % self.textifyXml(content))
                elif self.stack[2] == 'twitter':
                    #self.outputMarkdown('Twitter: %s\n\n' % content)
                    pass
                elif self.stack[2] == 'comments':
                    #if content.startswith('http'):
                    #    self.outputMarkdown('[Comments](%s)\n\n' % content)
                    #else:
                    #    self.outputMarkdown('Comments: %s\n\n' % content)
                    pass
                elif self.stack[2] in ('content', 'encoded'):
                    self.outputMarkdown(self.breakLines(content) + u'\n\n')
                elif self.stack[2] in ('copyright', 'rights'):
                    pass
                elif self.stack[2] in ('format', 'tags', 'publisher',
                                       'startdate', 'enddate',
                                       'origlink', 'commentrss',
                                       'sponsored', 'type', 'language',
                                       'adhocflag'):
                    # ignore
                    pass
                else:
                    self.complainAboutUnknownContent(content)
            else:
                self.complainAboutUnknownContent(content)
        else:
            self.complainAboutUnknownContent(content)

    def characters(self, content):
        self.content += content

def main():
    p = optparse.OptionParser('%prog <options> [filename|url]')
    # We currently don't need any of the original html2markdown
    # options..

    p.add_option("-m", "--multipart-boundary",
                     action="store", type="string", dest="boundary",
                     help="split into multiple parts")

    (options, args) = p.parse_args()

    handler = RssHandler(options.boundary)
    parser = xml.sax.make_parser()
    parser.setContentHandler(handler)
    if len(args) > 0:
        file_ = args[0]
        if len(args) > 1:
            p.error('Too many arguments')

        assert(not file_.startswith('http://'))
        assert(not file_.startswith('https://'))
        if file_ == '-':
            fd = sys.stdin
        else:
            fd = open(file_, 'rb')
    else:
        fd = sys.stdin

    parser.parse(fd)

if __name__ == "__main__":
    main()
