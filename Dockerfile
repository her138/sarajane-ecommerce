# Use the official PHP 8.2 Apache image
FROM php:8.2-apache

# Install MySQL PDO extension (required for your database connection)
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (optional – helps with clean URLs)
RUN a2enmod rewrite

# Copy all your project files into the Apache web root
COPY . /var/www/html/

# Set proper file permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Apache will listen on port 80 – Render will map it automatically
EXPOSE 80