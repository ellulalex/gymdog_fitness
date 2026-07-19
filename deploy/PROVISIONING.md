# Provisioning runbook — Gymdog platform

Standing up the Hetzner box (staging now, production later). Server:
`gymdog@46.224.160.95`, Ubuntu 24.04 LTS, CPX22.

> **Status:** this box is provisioned and live at **https://staging.gymdog.fitness**
> (Let's Encrypt TLS, Horizon + scheduler running, branch `staging-setup`).
> The steps below are the runbook for reproducing it / doing the prod cutover.

## 1. Provision the system (once, as root)

```bash
scp deploy/provision.sh root@46.224.160.95:/root/
ssh root@46.224.160.95 'bash /root/provision.sh'
```

Installs nginx + PHP 8.4-FPM + MySQL 8 + Redis, a dedicated `gymdog` app user
(same SSH key as root) with its own PHP-FPM pool, a 2 G swap file, and ufw.
Creates the `gymdog` database and writes its credentials to
`/home/gymdog/server-credentials.txt`.

## 2. Give the server read access to the private repo (deploy key)

```bash
ssh gymdog@46.224.160.95 'ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519 <<<y >/dev/null; cat ~/.ssh/id_ed25519.pub'
```

Add the printed key to GitHub → repo **Settings → Deploy keys** (read-only).

## 3. Clone and configure the app

```bash
ssh gymdog@46.224.160.95
git clone git@github.com:ellulalex/gymdog_fitness.git ~/app
cd ~/app
cp .env.example .env
# Edit .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://staging.gymdog.fitness,
#   DB_USERNAME/DB_PASSWORD/DB_DATABASE from ~/server-credentials.txt.
#   Use the MySQL socket (the app user is created for 'localhost'):
#     DB_HOST=localhost
#     DB_SOCKET=/var/run/mysqld/mysqld.sock
#   CACHE_STORE=redis, QUEUE_CONNECTION=redis, SESSION_DRIVER=redis,
#   a real ADMIN_PASSWORD, and (when ready) STRIPE_*/ANTHROPIC_API_KEY.
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force        # gymdog tenant + admin + GYMDOG5
```

Assets: `deploy.sh` builds them locally and rsyncs `public/build` up, so no Node
is needed on the server. From your Mac: `./deploy.sh main`.

## 4. Web server + queue + scheduler

```bash
# let nginx (www-data) traverse into the app user's home to serve public/
sudo chmod 755 /home/gymdog

# nginx site
sudo cp ~/app/deploy/nginx.conf /etc/nginx/sites-available/gymdog
sudo ln -sf /etc/nginx/sites-available/gymdog /etc/nginx/sites-enabled/gymdog
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Horizon (Redis queue) as a service
sudo cp ~/app/deploy/gymdog-horizon.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now gymdog-horizon

# Scheduler (content:generate weekly, publish-due hourly, etc.)
( crontab -l 2>/dev/null; echo "* * * * * cd /home/gymdog/app && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
```

## 5. DNS + TLS

1. Point **staging.gymdog.fitness** at `46.224.160.95` (Cloudflare A record).
2. TLS — pick one:
   - **Cloudflare proxied (orange cloud):** create an Origin Certificate in
     Cloudflare, install it in nginx, set SSL mode to **Full (strict)**.
   - **Direct / grey cloud:** `sudo certbot --nginx -d staging.gymdog.fitness`
     (install `certbot python3-certbot-nginx` first).

## Cutover (later)

Repeat the import against the live WP (`php artisan content:import-wordpress
--url=https://gymdog.fitness`), freeze WordPress, move the `gymdog.fitness` A
record to this box, set `APP_URL=https://gymdog.fitness`. Keep the old WP host
alive for ~a month as a rollback before decommissioning.
