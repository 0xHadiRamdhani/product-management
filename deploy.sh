#!/bin/bash

echo "🚀 Deploying Motorcycle Workshop Management System..."

# Build and start containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Run Laravel commands
docker-compose exec app composer install --optimize-autoloader --no-dev
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan optimize

echo "✅ Deployment completed successfully!"
echo "🌐 Application is available at: http://localhost:8000"
