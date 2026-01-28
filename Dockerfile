# =============================================================================
# SMF Forum with MOHAA Stats Plugin
# =============================================================================

# Stage 1: Pull the latest MOHAA Stats Integration Plugin
FROM alpine/git AS plugin-stage
WORKDIR /plugins
# Cache-bust ARG - change value or use --build-arg CACHEBUST=$(date +%s) to force fresh clone
ARG CACHEBUST=1
RUN echo "Cache bust: $CACHEBUST" && git clone https://github.com/MOHCentral/opm-stats-smf-integration.git .

# Stage 2: Final Image
FROM php:8.2-apache

# Install PHP extensions required by SMF
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# PHP Configuration for SMF
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/smf.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/smf.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/smf.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/smf.ini \
    && echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/smf.ini

# Apache config for clean URLs
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/smf.conf \
    && a2enconf smf

# Set working directory
WORKDIR /var/www/html

# Copy SMF files
COPY --chown=www-data:www-data . /var/www/html/

# Copy MOHAA Stats Plugin files from stage 1
COPY --from=plugin-stage --chown=www-data:www-data /plugins/smf-mohaa/Sources/. /var/www/html/Sources/
COPY --from=plugin-stage --chown=www-data:www-data /plugins/smf-mohaa/Themes/. /var/www/html/Themes/
COPY --from=plugin-stage --chown=www-data:www-data /plugins/smf-mohaa/install/mohaa_master_install.php /var/www/html/mohaa_install.php

# Create required directories with proper permissions
RUN mkdir -p /var/www/html/cache \
    /var/www/html/attachments \
    /var/www/html/avatars \
    /var/www/html/custom_avatar \
    /var/www/html/Packages \
    /var/www/html/smf-config \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 777 /var/www/html/cache \
    && chmod 777 /var/www/html/attachments \
    && chmod 777 /var/www/html/avatars \
    && chmod 777 /var/www/html/custom_avatar \
    && chmod 777 /var/www/html/Packages \
    && chmod 777 /var/www/html/smf-config \
    && touch /var/www/html/Settings.php /var/www/html/Settings_bak.php \
    && chmod 666 /var/www/html/Settings.php /var/www/html/Settings_bak.php \
    && chown www-data:www-data /var/www/html/Settings.php /var/www/html/Settings_bak.php

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
