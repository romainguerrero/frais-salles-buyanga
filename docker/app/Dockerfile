# Base image used is PHP php7.4.13 FPM.
# Feel free to override PHP version corresponding to your needs.
#
FROM        registry.acseo.co/acseoteam/symfony-docker-image/symfony:php7.4.13-1
LABEL       maintainer="ACSEO <contact@acseo.fr>"

ARG         USER_ID=1000

##
## Then, we change userid / groupid of www-data user (PHP-FPM) to ensure
## consistency between our different services and avoid permissions issues.
##
RUN         usermod -u $USER_ID www-data \
            && groupmod -g $USER_ID www-data \
            && chown -fR www-data:www-data /var/composer \
            && chown -fR www-data:www-data /var/www

#
# Then, we configure directory where all sources will then be stored and set
# permissions accordingly to www-data user.
#
COPY        --chown=www-data:www-data . /app

COPY        ./docker/app/entrypoint /usr/local/bin/app-entrypoint
RUN         chmod +x /usr/local/bin/app-entrypoint

WORKDIR     /app

USER        root

ENTRYPOINT  ["app-entrypoint"]

CMD         ["app-start"]
