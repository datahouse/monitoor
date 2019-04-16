#!/bin/bash

set -e

BASEDIR=$1
DOCKERTAG=$2
shift 2
PHPFILES="$@"

docker run --rm \
    --volume $BASEDIR:$BASEDIR \
    --user $USER \
    $DOCKERTAG \
    /bin/bash \
    -c "cd $BASEDIR; for FILE in $PHPFILES; do php -l \$FILE; done"
