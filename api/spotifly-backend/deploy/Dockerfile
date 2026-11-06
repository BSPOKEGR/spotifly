FROM bspokegr/php:8.4-bitnami-fpm-nginx AS base

# Override the default supervisord.conf
COPY settings/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure NGINX and PHP-FPM
COPY settings/nginx.default /etc/nginx/sites-available/default
ARG API_VIRTUALHOST_NAME
ARG NGINX_WORKER_CONNECTIONS
RUN sed -i "s/server_name SERVER_NAME;/server_name ${API_VIRTUALHOST_NAME};/" /etc/nginx/sites-available/default
RUN sed -i "s/worker_connections 768;/worker_connections ${NGINX_WORKER_CONNECTIONS};/" /etc/nginx/nginx.conf

# crontab
COPY settings/laravel-crontab /etc/
RUN crontab /etc/laravel-crontab

# Entrypoint and permissions
COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh

# Set supervisor to manage container processes
ENTRYPOINT ["/entrypoint.sh"]

FROM base AS local
COPY settings/custom_local.ini /opt/bitnami/php/etc/conf.d/custom.ini

FROM base AS production
COPY settings/custom_production.ini /opt/bitnami/php/etc/conf.d/custom.ini
