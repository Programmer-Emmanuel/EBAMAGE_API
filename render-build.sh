#!/usr/bin/env bash

composer install --no-dev --optimize-autoloader

mkdir -p storage/app/firebase

# On recrée le json ici ➜ Render l'aura même si GitHub ne l'a pas
echo "$FIREBASE_SERVICE_ACCOUNT_BASE64" | base64 -d > storage/app/firebase/ebamagenotificationsboutiques-firebase-adminsdk-fbsvc-96a227941d.json

php artisan config:cache
php artisan route:cache
php artisan view:cache
