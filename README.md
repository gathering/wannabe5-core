# wannabe5-core
Wannabe5 Core - under development

#### Local development - 2025-05-13
Needs docker and local php installation

```bash
cp .env.development .env
docker compose up -d
composer install
php artisan key:generate
php artisan serve
```