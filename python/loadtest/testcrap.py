#!/usr/bin/env python
#
# URL List Generator
# Made by: ianonavy
# Last updated: 14 August 2012
# License: Public Domain

from os.path import isfile
from random import random
from time import sleep
import httplib
import sys

NUMBER_OF_URLS = 20000
FORBIDDEN_SLEEP_TIME = 2  # Seconds
RANDOM_DELAY_MAX = .01  # Seconds
GENERATOR_BASE_URL = "www.randomwebsite.com"
GENERATOR_URL = "/cgi-bin/random.pl"
LIST_FILE_LOCATION = "urls.txt"
DEBUG = True


def main(count):
    """
    Randomly generates URLs by sending a HEAD request to a generator and
    reading the location heading.

    """
    if isfile(LIST_FILE_LOCATION):
        urls = []
        list_file = open(LIST_FILE_LOCATION, "r")
        line = list_file.readline()
        while line:
            urls.append(line)
            line = list_file.readline()
        list_file.close()
    with open(LIST_FILE_LOCATION, "a") as list_file:
        i = len(urls)
        while i < count:
            try:
                conn = httplib.HTTPConnection(GENERATOR_BASE_URL)
                conn.request("HEAD", GENERATOR_URL)
                res = conn.getresponse()
                conn.close()
                sleep(random() * RANDOM_DELAY_MAX)
                assert res.status == 302  # HTTP Redirect
                url = res.getheader("location")
                if url not in urls:
                    urls.append(url)
                    list_file.write(url + "\n")
                    if DEBUG:
                        print i, url
                    i += 1
            except AssertionError:
                if DEBUG:
                    print res.status, "error."
                if res.status == 403:
                    # Sleep for a bit just in case the website thinks you're
                    # DoSing them.
                    sleep(FORBIDDEN_SLEEP_TIME)
                pass

if __name__ == "__main__":
    try:
        count = int(sys.argv[1])
        print "Generating %d random URLs to urls.txt." % count
    except:
        count = NUMBER_OF_URLS
        print "Defaulting to %d." % NUMBER_OF_URLS

    main(count)
    
