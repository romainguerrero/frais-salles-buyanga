#!/bin/bash -x

RUN_CONTEXT=$1

if [ "$RUN_CONTEXT" != "local" ] ;
  then make install-docker;
fi
