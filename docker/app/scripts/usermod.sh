#!/bin/bash -x

# Check that given ids didn't exist before create and assigne them to www-data
if [ -z $(getent passwd $1) ] ;
then
    usermod -u $1 www-data;
fi
if [ -z $(getent group $2) ] ;
then
    groupmod -g $2 www-data;
fi
