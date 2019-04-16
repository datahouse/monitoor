# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>
#
# -*- test-case-name: datahouse.monitoor.test.test_xfrms -*-

"""
Transformations applied to files. Helper objects and routines.
"""

import os
import logging
import tempfile

from xml.sax.saxutils import quoteattr as xmlQuoteattr

from twisted.internet import defer
from twisted.python import log

from datahouse.monitoor.process import Process, \
    SMALL_PROCESS_TIMEOUT, LARGE_PROCESS_TIMEOUT

BIN_HASH_SUM = '/usr/bin/sha256sum'
BIN_HTML2TEXT = '/usr/bin/html2text'
BIN_HTML2MARKDOWN = '/usr/bin/html2markdown.py2'
BIN_PDF2TXT = '/usr/bin/pdf2txt'
BIN_XSLTPROC = '/usr/bin/xsltproc'

MAX_STDERR_LINES_LOGGED = 18

REQUIRED_BINARIES = [
    BIN_HASH_SUM,
    BIN_HTML2TEXT,
    BIN_HTML2MARKDOWN,
    BIN_PDF2TXT,
    BIN_XSLTPROC,
]

XSLT_FOR_XPATH = u"""
<xsl:stylesheet version="1.0" encoding="UTF-8"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output omit-xml-declaration="no"
              method="html" indent="yes" encoding="UTF-8" />
  <xsl:strip-space elements="*" />

  <!--
  Override the default text template to avoid copying text outside of
  the selection xpath
  -->
  <xsl:template match="text()|@*" />

  <!-- Select the real thing(s) we want and all children -->
  <xsl:template match="%(selector)s|%(selector)s//*[not(%(ignore)s)]">
    <xsl:copy>
      <xsl:apply-templates select="*[not(%(ignore)s)]|@*|text()" />
    </xsl:copy>
  </xsl:template>

  <!-- Copy attributes and texts -->
  <xsl:template match="%(selector)s//@*|%(selector)s//text()">
    <xsl:copy/>
  </xsl:template>

</xsl:stylesheet>
"""

# This interesting piece of XSLT tries to generate an RSS feed from a
# fuw.ch index page for a certain category. Not for the faint of
# heart, that damn XSLT crap.
XSLT_FOR_FINANZ_UND_WIRTSCHAFT = u"""
<xsl:stylesheet version="1.0" encoding="UTF-8"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:atom="http://www.w3.org/2005/Atom">
  <xsl:output omit-xml-declaration="no"
              method="xml" indent="yes" encoding="UTF-8" />
  <xsl:strip-space elements="*" />

  <!--
  Override the default text template to avoid copying text outside of
  the selection xpath
  -->
  <xsl:template match="text()|@*" />

  <xsl:template match="//body">
    <feed>
      <xsl:apply-templates select="*"/>
    </feed>
  </xsl:template>

  <xsl:template match="li[article/p and .//header//a[contains(@href, 'fuw.ch/article')]]">
    <entry>
      <title><xsl:value-of select=".//header//text()"/></title>
      <link rel="alternate" type="text/html">
        <xsl:attribute name="href">
          <xsl:value-of select=".//header//a/@href"/>
        </xsl:attribute>
      </link>
      <id><xsl:value-of select="@id"/></id>
      <content type="html">
        <xsl:copy-of select=".//article/p"/>
      </content>
    </entry>
  </xsl:template>

</xsl:stylesheet>
"""

def onlyQuoteAttr(str):
    """saxutils' quoteattr adds the quotes, but we need the plain value with
    double quotes replaced.
    """
    result = xmlQuoteattr("'" + str)
    assert(result[0] == '"')
    assert(result[1] == "'")
    assert(result[-1] == '"')
    return result[2:-1]

def checkRequiredBinaries():
    missing_binaries = []
    for path in REQUIRED_BINARIES:
        if not os.path.isfile(path):
            missing_binaries.append(path)
    if len(missing_binaries) > 0:
        print("ERROR: missing required binaries: %s"
              % ', '.join(missing_binaries))
        sys.exit(1)

@defer.inlineCallbacks
def calcHash(path=None, data=None):
    # Use an external process to keep the main event loop clean of
    # CPU intensive tasks.
    cmd = [BIN_HASH_SUM]
    input = None
    if path is None:
        input = data
    else:
        cmd.append(path)
        assert(data is None)
    proc = Process(cmd, input=input, timeout=SMALL_PROCESS_TIMEOUT)
    exitCode = yield proc.run()
    if exitCode != 0:
        raise ValueError("Hashing binary returned non-zero exit code: %d" % (
            exitCode,))
    lines = proc.getOutStr().strip().split('\n')
    if len(lines) != 1:
        raise ValueError(
            "Hashing binary returned more than one line of output.")
    outHash, outFn = lines[0].strip().split('  ')
    defer.returnValue(outHash.strip().decode("hex"))

def calcFileHash(path):
    return calcHash(path=path)

def calcStrHash(data):
    return calcHash(data=data)

def xfrmCleanup(result, filesToDelete):
    for path in filesToDelete:
        os.unlink(path)
    return result

def xfrmCompleted(results, procs, mimeType):
    errors = []
    warnings = []
    mustAbort = False
    for i in range(len(results)):
        (success, result) = results[i]
        proc = procs[i]
        if not success:
            failure = result
            msg = "Exception in process %s: %s" % (
                repr(proc.args[0]), str(result))
            errors.append((proc.pid, msg))
        else:
            exitCode = result
            xfrmErrs = proc.getErrStr()
            lines = xfrmErrs.split('\n')
            if len(lines) > MAX_STDERR_LINES_LOGGED:
                lines = lines[:MAX_STDERR_LINES_LOGGED] + \
                    ["... (plus %d more lines of stderr output)" % (
                        len(lines) - MAX_STDERR_LINES_LOGGED)]

            if exitCode != 0:
                mustAbort = True
                if len(xfrmErrs) > 0:
                    msg = "Transformation failed in process %s: %s" % (
                        repr(proc.args[0]), str(result))
                    errors.append((proc.pid, msg))
                    for line in lines:
                        errors.append((proc.pid, ">>> %s" % line))
                else:
                    msg = "Transformation failed in process %s: %s %s" % (
                        repr(proc.args[0]), str(result),
                        "(w/o any messages in stderr)")
                    errors.append((proc.pid, msg))
            elif len(xfrmErrs) > 0:
                msg = "xfrm process %s has non-empty error outputs:" % (
                    repr(proc.args[0]),)
                warnings.append((proc.pid, msg))
                for line in lines:
                    warnings.append((proc.pid, ">>> %s" % line))
    outData = None if mustAbort else procs[-1].getOutStr()
    return mustAbort, errors, warnings, outData, mimeType

def planXfrms(document, commands, xfrmArgs):
    commands = [x.strip() for x in commands.split('|')]
    if commands[0] != 'pdf2txt':
        commands = ['encoding_sanitizer'] + commands
    # Before 'html2markdown', always pipe data through an HTML entity decoder
    # for proper support of umlauts.
    if commands[-1] == 'html2markdown':
        commands = commands[:-1] + ['html_entity_resolver', 'html2markdown']
    log.msg("media type: %s, media params: %s, commands: %s"
            % (document.mediaType, repr(document.mediaParams), repr(commands)),
            logLevel=logging.DEBUG, system="xfrms")
    # FIXME: check for proper mime types
    mimeTypes = [document.mediaType]
    pipeOutputs = []
    procs = []
    filesToDelete = set()
    outFileName = None
    for i in range(len(commands)):
        isFirstCommand = (i == 0)
        isLastCommand = (i == len(commands) - 1)
        # The very first command reads from the file on disk, the last
        # one writes to a file, again. Everything in between is smoked
        # in pipes.
        if isFirstCommand:
            input = open(document.tmpFileName, 'rb')
        else:
            input = pipeOutputs[-1]
        if isLastCommand:
            output = None
        else:
            pipeout, pipein = os.pipe()
            output = pipein
            pipeOutputs.append(pipeout)
        cmd = commands[i]
        if cmd == 'html2text':
            proc = Process([BIN_HTML2TEXT, "-utf8"],
                            input=input, output=output,
                            timeout=LARGE_PROCESS_TIMEOUT)
            mimeTypes.append('text/plain')
        elif cmd == 'html_entity_resolver':
            custom_bin = os.path.join(
                os.path.dirname(__file__), "..", "html_entity_resolver.py")
            args = [custom_bin, '-']
            proc = Process(args, input=input, output=output,
                           timeout=SMALL_PROCESS_TIMEOUT)
            mimeTypes.append(mimeTypes[-1])
        elif cmd == 'encoding_sanitizer':
            # Must only ever occur at the very beginning of the chain.
            assert(isFirstCommand)
            custom_bin = os.path.join(
                os.path.dirname(__file__), "..", "encoding_sanitizer.py")
            args = [custom_bin, '-']
            if 'charset' in document.mediaParams:
                args.append(document.mediaParams['charset'])
            proc = Process(args, input=input, output=output,
                           timeout=SMALL_PROCESS_TIMEOUT)
            mimeTypes.append(mimeTypes[-1])
        elif cmd == 'html2markdown':
            # Since we use an UTF-8 sanitizer, we can always simply
            # pipe this to html2markdown (which chokes w/o a correct
            # encoding, but defaults to UTF-8).
            args = ['--ignore-emphasis', '--ignore-links',
                    '--ignore-images']
            proc = Process([BIN_HTML2MARKDOWN] + args,
                           input=input, output=output,
                           timeout=LARGE_PROCESS_TIMEOUT)
            mimeTypes.append('text/markdown')
        elif cmd == 'pdf2txt':
            # Unfortunately, crappy pdf2txt doesn't support reading
            # from stdin.
            if i != 0:
                raise ValueError("pdf2txt supported only as the very " +
                                "first step in a transformation chain.")
            proc = Process([BIN_PDF2TXT, "-c", "utf-8",
                            document.tmpFileName],
                            input=None, output=output,
                            timeout=LARGE_PROCESS_TIMEOUT)
            mimeTypes.append('text/plain')
        elif cmd == 'rss2markdown':
            custom_bin = os.path.join(
                os.path.dirname(__file__), "..", "rss2markdown.py")
            args = [custom_bin, '-']
            proc = Process(args, input=input, output=output,
                           timeout=SMALL_PROCESS_TIMEOUT)
            mimeTypes.append('text/markdown')
        elif cmd == 'rss2markdown-split':
            custom_bin = os.path.join(
                os.path.dirname(__file__), '..', 'rss2markdown.py')
            boundary = '=' * 20 + '548487216' + '=' * 2
            args = [custom_bin, '--multipart-boundary', boundary, '-']
            proc = Process(args, input=input, output=output,
                               timeout=SMALL_PROCESS_TIMEOUT)
            mimeTypes.append('multipart/mixed')
        elif cmd in ('xpath', 'xsltproc', 'fuw.ch2rss'):
            # Maybe run stuff through htmltidy, like so:
            # cat /tmp/out | \
            #   tidy -n -q -asxhtml 2> /dev/null | \
            #   xsltproc --html /tmp/test.xslt -

            args = "--encoding utf-8 --nonet --novalid --nomkdir".split(' ')
            if mimeTypes[-1] == 'text/html':
                args += ['--html']

            # Write an XSLT stylesheet for the transformation.
            f = tempfile.NamedTemporaryFile(delete=False)
            if cmd == 'xsltproc':
                # The general-purpose XSLT transformation case.
                if 'xslt' not in xfrmArgs:
                    raise ValueError(
                        "missing xslt argument for xsltproc command")
                f.write(xfrmArgs['xslt'])
            elif cmd == 'xpath':
                if 'xpath' not in xfrmArgs:
                    raise ValueError(
                        "missing xpath argument for xpath command")
                if xfrmArgs.has_key('xpath-ignore') and \
                  xfrmArgs['xpath-ignore'] != '':
                    xpathIgnore = onlyQuoteAttr(xfrmArgs['xpath-ignore'])
                else:
                    xpathIgnore = 'false'

                xslt = XSLT_FOR_XPATH % {
                    'selector': onlyQuoteAttr(xfrmArgs['xpath']),
                    'ignore': xpathIgnore
                    }
                f.write(xslt.encode('utf-8'))
            elif cmd == 'fuw.ch2rss':
                f.write(XSLT_FOR_FINANZ_UND_WIRTSCHAFT)
            else:
                assert(False)
            xsltPath = f.name
            f.close()

            filesToDelete.add(xsltPath)

            proc = Process([BIN_XSLTPROC] + args + [xsltPath, '-'],
                            input=input, output=output,
                            timeout=LARGE_PROCESS_TIMEOUT)
            proc.setGoodExitCodes([0, 6])
            # Assume transformations simple enough to still yield
            # html-ish output.
            mimeTypes.append('text/html')
        else:
            raise ValueError("Unknown transformation specified.")
        procs.append(proc)

    dl = []
    for proc in procs:
        d = proc.run()
        dl.append(d)

    d = defer.DeferredList(dl, consumeErrors=True)
    d.addBoth(xfrmCleanup, filesToDelete)
    d.addCallback(xfrmCompleted, procs, mimeTypes[-1])
    return d

__all__ = ['calcFileHash', 'calcStrHash', 'checkRequiredBinaries']
