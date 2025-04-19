FROM php:8.2-cli

# Установим необходимые расширения
RUN apt-get update && apt-get install -y \
    unzip curl git zip libzip-dev \
    && docker-php-ext-install zip

# Установим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установим phpunit
RUN composer global require phpunit/phpunit \
    && ln -s /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit

# Установим рабочую директорию
WORKDIR /var/www/app

# Копируем проект
COPY . .

# Установим зависимости
RUN composer install