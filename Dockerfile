FROM php:8.4-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    sqlite-dev \
    unzip \
    libzip-dev

RUN docker-php-ext-install pdo pdo_sqlite pdo_pgsql zip

# Set working directory
WORKDIR /app

# Copy application code
COPY . .

# Ensure storage and bootstrap/cache directories are writable
RUN chmod -R 777 storage bootstrap/cache

# Expose port 3000
EXPOSE 3000

# Run artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=3000"]
