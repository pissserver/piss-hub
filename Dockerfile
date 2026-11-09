FROM php:5.6-apache

RUN docker-php-ext-install mysql mysqli

RUN a2enmod rewrite

RUN echo "date.timezone = Asia/Jakarta" > /usr/local/etc/php/conf.d/ini.ini || true

COPY . /var/www/html/

# phpMyAdmin-style upload limit
RUN echo "upload_max_filesize = 512M\npost_max_size = 512M\nmemory_limit = 512M\nmax_execution_time = 600\nmax_input_time = 600" > /usr/local/etc/php/conf.d/uploads.ini

RUN chown -R www-data:www-data /var/www/html && \
    sed -i 's;RewriteBase /timbangan/;RewriteBase /;' /var/www/html/timbangan/.htaccess && \
    sed -i 's;RewriteBase /lapak/;RewriteBase /;' /var/www/html/lapak/.htaccess && \
    sed -i 's;RewriteBase /listrik/;RewriteBase /;' /var/www/html/listrik/.htaccess

EXPOSE 80
