FROM arrim/basephp:81m

ARG uname=www-data
ARG gid=33
ARG uid=33

WORKDIR /srv/www

COPY --chown=$uname:$uname . /srv/www

COPY ./docker/config/production/nginx/conf.d/default.conf /etc/nginx/http.d/default.conf
COPY ./docker/config/production/supervisord.conf /etc/supervisord.conf

RUN ls -al /srv/www/vendor
RUN ls -al /srv/www/public/admin
