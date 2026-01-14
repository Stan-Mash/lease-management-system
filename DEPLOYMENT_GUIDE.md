# Chabrin Lease Management System - Production Deployment Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [Application Deployment](#application-deployment)
4. [Database Setup](#database-setup)
5. [Web Server Configuration](#web-server-configuration)
6. [SSL Configuration](#ssl-configuration)
7. [Queue & Scheduler Setup](#queue--scheduler-setup)
8. [Post-Deployment](#post-deployment)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Minimum Server Requirements

- **OS**: Ubuntu 22.04 LTS or higher
- **CPU**: 2+ cores
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 50GB SSD minimum
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 14+
- **Redis**: 6.0+ (for caching and queues)
- **Node.js**: 18+ (for asset compilation)

### Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and required extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-pgsql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip \
    php8.2-gd php8.2-intl php8.2-soap

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js & NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-nginx

# Install Supervisor (for queue workers)
sudo apt install -y supervisor

# Install fail2ban (for security)
sudo apt install -y fail2ban
```

---

## Server Setup

### 1. Create Application User

```bash
# Create dedicated user for the application
sudo adduser chabrin --disabled-password --gecos ""

# Add to www-data group
sudo usermod -aG www-data chabrin
```

### 2. Configure Firewall

```bash
# Install UFW if not installed
sudo apt install -y ufw

# Allow SSH (change 22 to custom port if modified)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

### 3. Secure MySQL

```bash
# Run security script
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p

mysql> CREATE DATABASE chabrin_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> CREATE USER 'chabrin_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
mysql> GRANT ALL PRIVILEGES ON chabrin_production.* TO 'chabrin_user'@'localhost';
mysql> FLUSH PRIVILEGES;
mysql> EXIT;
```

### 4. Configure Redis

```bash
# Edit Redis config
sudo nano /etc/redis/redis.conf

# Set password (uncomment and set)
requirepass YOUR_REDIS_PASSWORD_HERE

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

---

## Application Deployment

### Method 1: Git Deployment (Recommended)

```bash
# Switch to application user
sudo su - chabrin

# Clone repository
cd /home/chabrin
git clone https://github.com/your-org/chabrin-lease-system.git production
cd production

# Checkout production branch
git checkout main  # or your production branch

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci --production

# Build assets
npm run build
```

### Method 2: Manual Upload

```bash
# On local machine, create deployment package
composer install --no-dev --optimize-autoloader
npm run build
tar -czf chabrin-deploy.tar.gz \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/*.log' \
    .

# Upload to server
scp chabrin-deploy.tar.gz chabrin@your-server:/home/chabrin/

# On server
cd /home/chabrin
tar -xzf chabrin-deploy.tar.gz -C production/
```

### 3. Environment Configuration

```bash
cd /home/chabrin/production

# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env
```

**Critical `.env` settings:**

```env
APP_NAME="Chabrin Lease Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Generate this with: php artisan key:generate
APP_KEY=base64:GENERATED_KEY_HERE

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chabrin_production
DB_USERNAME=chabrin_user
DB_PASSWORD=YOUR_DATABASE_PASSWORD

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Africa's Talking (SMS)
AFRICAS_TALKING_USERNAME=your-username
AFRICAS_TALKING_API_KEY=your-api-key

# File Storage (use S3 for production)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=chabrin-production

# Security
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

### 4. Set Permissions

```bash
cd /home/chabrin/production

# Set ownership
sudo chown -R chabrin:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make storage and cache writable
chmod -R 775 storage bootstrap/cache

# Secure .env file
chmod 600 .env
```

### 5. Generate Application Key

```bash
php artisan key:generate --force
```

---

## Database Setup

```bash
# Run migrations
php artisan migrate --force

# Create initial zones (example)
php artisan tinker

Zone::create(['name' => 'Zone A - Westlands', 'code' => 'ZN-A', 'is_active' => true]);
Zone::create(['name' => 'Zone B - Kilimani', 'code' => 'ZN-B', 'is_active' => true]);
Zone::create(['name' => 'Zone C - CBD', 'code' => 'ZN-C', 'is_active' => true]);
exit

# Seed initial data (if needed)
php artisan db:seed --class=ProductionSeeder --force
```

### Create Admin User

```bash
php artisan tinker

$admin = User::create([
    'name' => 'Administrator',
    'email' => 'admin@chabrin.com',
    'password' => Hash::make('SECURE_PASSWORD_HERE'),
    'role' => 'super_admin',
    'is_active' => true,
]);

echo "Admin user created with email: " . $admin->email;
exit
```

---

## Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/chabrin`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /home/chabrin/production/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    # Increase upload size
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Disable access to sensitive files
    location ~ /\.(env|git|gitignore|htaccess) {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/chabrin /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### PHP-FPM Optimization

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
php_admin_flag[allow_url_fopen] = off
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

---

## SSL Configuration

### Using Let's Encrypt (Free)

```bash
# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run

# Certificates auto-renew via cron
```

### Using Custom SSL Certificate

Place certificate files in `/etc/ssl/certs/` and update Nginx config:

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/ssl/certs/yourdomain.crt;
    ssl_certificate_key /etc/ssl/certs/yourdomain.key;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # ... rest of config
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## Queue & Scheduler Setup

### Configure Supervisor for Queue Workers

Create `/etc/supervisor/conf.d/chabrin-worker.conf`:

```ini
[program:chabrin-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/chabrin/production/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=chabrin
numprocs=4
redirect_stderr=true
stdout_logfile=/home/chabrin/production/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chabrin-worker:*

# Check status
sudo supervisorctl status
```

### Configure Cron for Scheduler

```bash
sudo crontab -e -u chabrin

# Add this line
* * * * * cd /home/chabrin/production && php artisan schedule:run >> /dev/null 2>&1
```

---

## Post-Deployment

### 1. Optimize Application

```bash
cd /home/chabrin/production

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 2. Verify Installation

```bash
# Check system health
php artisan about

# Test database connection
php artisan migrate:status

# Test queue connection
php artisan queue:monitor

# Test cache
php artisan cache:clear
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test'); // Should return 'value'
exit
```

### 3. Security Scan

```bash
# Check for vulnerabilities
composer audit

# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Verify .env is secure
ls -la .env
```

---

## Monitoring & Maintenance

### Log Monitoring

```bash
# Application logs
tail -f storage/logs/laravel.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Worker logs
tail -f storage/logs/worker.log
```

### Database Backup

Create `/home/chabrin/scripts/backup-db.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/chabrin/backups"
mkdir -p $BACKUP_DIR

mysqldump -u chabrin_user -pYOUR_PASSWORD chabrin_production | gzip > $BACKUP_DIR/chabrin_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "chabrin_*.sql.gz" -mtime +30 -delete

echo "Backup completed: chabrin_$DATE.sql.gz"
```

Make executable and add to cron:

```bash
chmod +x /home/chabrin/scripts/backup-db.sh

crontab -e
# Add daily backup at 2 AM
0 2 * * * /home/chabrin/scripts/backup-db.sh >> /home/chabrin/logs/backup.log 2>&1
```

### Update Application

```bash
cd /home/chabrin/production

# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and re-cache
php artisan optimize:clear
php artisan optimize

# Restart queue workers
sudo supervisorctl restart chabrin-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.2-fpm
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log

# Check Nginx error logs
sudo tail -50 /var/log/nginx/error.log

# Check permissions
ls -la storage/ bootstrap/cache/

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 2. Database Connection Failed

```bash
# Test connection
php artisan tinker
DB::connection()->getPdo();
exit

# Check MySQL status
sudo systemctl status mysql

# Check credentials in .env
cat .env | grep DB_
```

#### 3. Queue Not Processing

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart chabrin-worker:*

# Check worker logs
tail -50 storage/logs/worker.log

# Manually process queue
php artisan queue:work --once
```

#### 4. High Memory Usage

```bash
# Check processes
top -u chabrin

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart workers
sudo supervisorctl restart chabrin-worker:*

# Clear application cache
php artisan cache:clear
```

#### 5. Slow Performance

```bash
# Enable OPcache
sudo nano /etc/php/8.2/fpm/php.ini

# Add/uncomment:
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check query performance
php artisan telescope:install  # For debugging
```

---

## Rollback Procedure

If deployment fails:

```bash
cd /home/chabrin

# Restore previous version
git reset --hard HEAD~1

# Restore database from backup
gunzip < backups/chabrin_TIMESTAMP.sql.gz | mysql -u chabrin_user -p chabrin_production

# Clear cache
php artisan optimize:clear

# Restart services
sudo supervisorctl restart chabrin-worker:*
sudo systemctl reload php8.2-fpm
```

---

## Additional Resources

- Laravel Deployment: https://laravel.com/docs/deployment
- Nginx Best Practices: https://www.nginx.com/blog/
- PHP-FPM Tuning: https://www.php.net/manual/en/install.fpm.php
- MySQL Optimization: https://dev.mysql.com/doc/refman/8.0/en/optimization.html

---

**Last Updated**: 2026-01-14
**Status**: Production Ready
