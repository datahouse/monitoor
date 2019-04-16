#!/bin/bash

PIDFILE=/srv/backend-workdir/monitoor-backend.pid

rm -rf $PIDFILE

# Required just to find db.conf.json, I think.
cd /srv/backend-workdir

exec /usr/bin/python \
    /usr/bin/twistd \
         --pidfile=$PIDFILE \
         --logfile=/srv/backend-workdir/logs/ignoredfile.log \
         -ny \
         /srv/backend-code/monitoor.tac
