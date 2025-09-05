# Dockerfile для PHP-микросервиса

# Используем официальный образ PHP 8.1 FPM
FROM php:8.1-fpm

# Устанавливаем зависимости, необходимые для расширений PHP
RUN apt-get update && apt-get install -y \
    librdkafka-dev \
    libpq-dev \
    git \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && docker-php-ext-install pdo pdo_pgsql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /app

# Копируем файлы проекта
COPY . .

# Устанавливаем зависимости Composer
#RUN composer install

# Открываем порт 9000 для FPM
EXPOSE 9000

# Запускаем PHP-FPM
CMD ["php-fpm"]
