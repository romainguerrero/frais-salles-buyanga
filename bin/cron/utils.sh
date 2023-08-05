#!/bin/bash -e

function init {
    SCRIPTDIR=${0%/*}

    cd $SCRIPTDIR/../../
    BASEDIR=$(pwd)

    source $BASEDIR/.env;

    LOG_FILE=$BASEDIR/var/log/cron.$(date +"%a").log
}

function log {
    echo "$(date +"%Y%m%d%H%M%S") | $1" >> "$LOG_FILE"
}

function log_nl {
    echo "" >> "$LOG_FILE"
}
