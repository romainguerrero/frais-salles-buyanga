#!/bin/bash -e

SCRIPT_DIR=${0%/*}
source "$SCRIPT_DIR/utils.sh";

init

if [ -f "$BASEDIR/var/cron/$0.ignore" ]; then
    log "IGNORED : $0"
    log_nl
    exit 0
fi

log "START   : $0"

##> CONFIGURE THE SYMFONY COMMAND HERE
$PHP_CMD "$BASEDIR/bin/console" list >> "$LOG_FILE" 2>&1
##< CONFIGURE THE SYMFONY COMMAND HERE

log "END     : $0"
log_nl
