FROM registry.acseo.co/acseoteam/symfony-docker-image/symfony:php8.1.9-1
LABEL maintainer="ACSEO <contact@acseo.fr>"

#
# www-data user config
# The user and group 33 are default www-data ids on debian/ubuntu environments and for security reasons we shouldn't change them if we don't have to (those ids are protected, so we couldn't login with, and changing them could open security breach)
#
ARG GROUP_ID=33
ARG USER_ID=33
COPY ./docker/app/scripts/usermod.sh /tmp/usermod.sh
RUN chmod +x /tmp/usermod.sh && /tmp/usermod.sh $USER_ID $GROUP_ID
RUN chown -fR www-data:www-data /var/composer \
        && chown -fR www-data:www-data /var/www \
        && rm /tmp/usermod.sh

#
# Then, we configure directory where all sources will then be stored and set
# permissions accordingly to www-data user.
#
COPY --chown=www-data:www-data . /app

COPY ./docker/app/entrypoint /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /app

USER www-data

ARG RUN_CONTEXT=''
ENV RUN_CONTEXT ${RUN_CONTEXT}
ENV EXEC_IN_APP=''
ENV RUN_IN_APP=eval
ENV RUN_IN_APP_WITH_ADMIN=eval
ENV RUN_IN_APP_WITH_DEPS=eval

RUN chmod +x /app/docker/app/scripts/make_install.sh \
        && /app/docker/app/scripts/make_install.sh $RUN_CONTEXT

USER root

ENTRYPOINT ["app-entrypoint"]

CMD ["/usr/bin/supervisord"]
