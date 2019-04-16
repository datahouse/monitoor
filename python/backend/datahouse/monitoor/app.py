# Copyright (c) Datahouse AG
# Author: Markus Wanner (mwa) <markus.wanner@datahouse.ch>

"""
The main application code, combining the spider and database parts.
"""

from __future__ import print_function
import re
import os
import json
import sys
import logging

from twisted.application import service
from twisted.python.log import ILogObserver, FileLogObserver

from datahouse.monitoor import xfrms
from datahouse.monitoor.crawler import CrawlerService
from datahouse.monitoor.dbapi import DbInterfaceService
from datahouse.monitoor import logfile
from datahouse.monitoor.scheduler import Scheduler

def substEnvVars(s):
    while True:
        m = re.search('\$\{(\w+)\}', s)
        if not m:
            break
        varname = m.group(1)
        val = os.getenv(varname)
        if not val:
            print("ERROR: variable %s not defined (db.conf.json)" % (
                varname,))
            sys.exit(1)
        s = re.sub('\$\{%s\}' % varname, val, s)
    return s

def loadDatabaseConfig():
    db_config = None
    if os.getenv('database_PORT_5432_TCP_ADDR') is not None:
        # try to load the database configuration from docker-compose
        # (v1) compatible environment variables, assuming the Postgres
        # container was called 'database'.
        db_config = {
            'type': 'postgres',
            'host': os.getenv('database_PORT_5432_TCP_ADDR'),
            'port': os.getenv('database_PORT_5432_TCP_PORT') or 5432,

            # FIXME: not sure how composer would pass these...
            'database': os.getenv('POSTGRES_DB'),
            'username': os.getenv('POSTGRES_USER'),
            'password': os.getenv('POSTGRES_PASSWORD')
            }
    else:
        # load the database configuration from the project-wide json
        # config
        here = os.path.realpath(os.path.dirname(__file__))
        db_config_paths = [here + "/../../conf/db.docker.conf.json",
                           here + "/../../conf/.db.conf.json",
                           here + "/../../../../.db.conf.json"]

        for path in db_config_paths:
            if os.path.exists(path):
                try:
                    with open(path, 'r') as f:
                        db_config = json.load(f)
                except IOError as e:
                    print("ERROR: unable to read database configuration file:\n%s"
                          % e, file=sys.stderr)
                    sys.exit(1)
                break

        # Handle config files specifying more than one database.
        if db_config.has_key('default') and not db_config.has_key('type'):
            db_config = db_config['default']

    if db_config is None:
        print("ERROR: missing database configuration.")
        sys.exit(1)

    for key in db_config.keys():
        db_config[key] = substEnvVars(db_config[key])

    assert(db_config['type'] == "postgres")
    dsn_parts = ["dbname=%s" % db_config['database']]
    if 'username' in db_config:
        dsn_parts.append("user=%s" % db_config['username'])
    if 'password' in db_config:
        dsn_parts.append("password=%s" % db_config['password'])
    if 'host' in db_config:
        dsn_parts.append("host=%s" % db_config['host'])
    if 'port' in db_config:
        dsn_parts.append("port=%s" % db_config['port'])

    return ' '.join(dsn_parts)

def createMonApplication():
    """
    Creates the main monitoor spider Application object to be passed to
    twistd to run the monitoor spider.

    @rtype: twisted.application.service.Application
    """
    xfrms.checkRequiredBinaries()

    daemonize = True
    useLogging = False
    # These must actually match with options passed to twistd, which is a bit
    # weird.
    for arg in sys.argv:
        if arg.startswith('-') and not arg.startswith('--'):
            if 'n' in arg:
                daemonize = False
        if arg.startswith('-') or arg.startswith('--logfile='):
            useLogging = True

    app = service.Application('monitoor spider app')
    if daemonize or useLogging:
        logDir = os.path.join(os.getcwd(), 'logs')
        if not os.path.exists(logDir):
            os.mkdir(logDir)
        logFile = logfile.DailyLogFile("monitoor.log", logDir,
                                       defaultMode=0o640)
        logger = logfile.LevelFileLogObserver(logFile, level=logging.INFO)
        app.setComponent(ILogObserver, logger.emit)

    dbi = DbInterfaceService(loadDatabaseConfig())
    dbi.setServiceParent(app)

    crawlService = CrawlerService()
    crawlService.setServiceParent(app)

    scheduler = Scheduler(dbi, crawlService)
    scheduler.setServiceParent(app)

    dbi.registerAddJobsCallback(scheduler.addJobs)
    dbi.registerDropJobsCallback(scheduler.dropJobs)
    dbi.registerLiveTaskCallback('test-xpath',
                                 scheduler.processTestXpathTask)

    return app
