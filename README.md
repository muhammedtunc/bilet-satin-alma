# BiletSatınAlmaPlatformu - Docker

Özet:
- PHP 8.2 + Apache, SQLite, Dompdf (composer ile) kullanır.
- Çalıştırma: docker-compose ile.

Hazırlık:
1. Proje kökünde bir `data` klasörü oluşturun:
   mkdir -p data
   touch data/database.sqlite
   chown -R $(id -u):$(id -g) data
   # (docker-compose çalıştırınca ownership tekrar ayarlanabilir)

2. Eğer composer.json yoksa lokal makinede composer ile dompdf ekleyin (isteğe bağlı):
   composer require dompdf/dompdf

Çalıştırma:
docker-compose build
docker-compose up -d

Sunucu: http://localhost:8080
