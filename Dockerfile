FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite headers

COPY nicoleconteudobr.site/ /var/www/html/

RUN if [ -f /var/www/html/nicolle/home57cf.html ]; then \
      cp /var/www/html/nicolle/home57cf.html /var/www/html/index.html; \
    fi

RUN chmod -R 755 /var/www/html

EXPOSE 10000

ENV APACHE_DOCUMENT_ROOT=/var/www/html

CMD ["apache2-foreground"]
