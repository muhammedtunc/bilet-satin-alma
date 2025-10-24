FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www/html

# Sistem paketleri ve gerekli PHP uzantıları
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip unzip git \
    libpng-dev libjpeg-dev libfreetype6-dev fontconfig \
    libxml2-dev libonig-dev libsqlite3-dev libzip-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd pdo_sqlite mbstring xml zip \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

# Composer binary (resmi composer image'tan)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ÖNEMLİ: önce sadece composer dosyalarını kopyala -> cache'lenebilir katman
COPY composer.json composer.lock* /var/www/html/ 

# Eğer composer.json varsa bağımlılıkları yükle
RUN if [ -f composer.json ]; then \
      composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader; \
    fi

# Garantile: dompdf yüklü değilse yükle (build sırasında kesin yükler)
RUN php -r "echo (int)file_exists('vendor/autoload.php');" || true
RUN composer show dompdf/dompdf >/dev/null 2>&1 || composer require --no-interaction --prefer-dist dompdf/dompdf

# Ardından proje dosyalarını kopyala (vendor korunur)
COPY . /var/www/html

# İzin ayarları
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80
CMD ["apache2-foreground"]