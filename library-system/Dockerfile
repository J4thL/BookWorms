
FROM php:8.2-apache
 
# Enable Apache mod_rewrite
RUN a2enmod rewrite
 
# Install PHP extensions required for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql
 
# Set working directory
WORKDIR /var/www/html
 
# Copy all project files
COPY . .
 
# Set Apache document root to project root
ENV APACHE_DOCUMENT_ROOT /var/www/html
 
# Allow .htaccess overrides
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf
 
# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
 
EXPOSE 80
