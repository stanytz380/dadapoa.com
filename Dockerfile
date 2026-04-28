FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install required PHP extensions (curl, json, mbstring)
RUN docker-php-ext-install curl json mbstring

# Copy project files into Apache document root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
