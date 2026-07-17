# syntax=docker/dockerfile:1

# ── Stage 1: PHP dependencies (production only) ──────────────────────────────
# Built before the assets stage because the front-end build imports Flux's CSS
# and @source-scans its blade stubs out of vendor/ (see resources/css/app.css).
# serversideup's CLI image ships Composer and the same PHP extension set as the
# runtime (intl, etc.), so dependency resolution matches production exactly.
FROM serversideup/php:8.5-cli AS vendor
USER root
RUN install-php-extensions intl
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev --no-scripts --no-interaction \
      --prefer-dist --optimize-autoloader

# ── Stage 2: front-end assets (Vite + Tailwind v4) ──────────────────────────
FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ── Stage 3: runtime (production-tuned php-fpm + nginx) ──────────────────────
FROM serversideup/php:8.5-fpm-nginx
USER root
RUN install-php-extensions intl

# Campaign emails render MJML by shelling out to Node at REQUEST time
# (Spatie\Mjml runs node_modules/mjml). Ship a real `node` binary — the renderer
# searches PATH, which includes /usr/local/bin.
COPY --from=node:22-slim /usr/local/bin/node /usr/local/bin/node
RUN node -v

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor       ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY --from=assets --chown=www-data:www-data /app/node_modules ./node_modules

# Back to the image's non-root default for runtime.
USER www-data
