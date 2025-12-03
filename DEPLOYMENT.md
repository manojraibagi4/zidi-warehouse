# Production Deployment Checklist

This document provides a step-by-step guide for deploying the Warehouse Management System to production.

## Pre-Deployment Checklist

### 1. Environment Configuration

- [ ] Copy `.env.example` to `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Update database credentials in `.env`:
  - [ ] `DB_HOST`
  - [ ] `DB_USER`
  - [ ] `DB_PASSWORD`
  - [ ] `DB_NAME`
- [ ] Update `CORS_ALLOWED_ORIGINS` with your production domain(s)
- [ ] Verify all environment variables are set correctly

### 2. Database Setup

- [ ] Create production database
- [ ] Import database schema from `tables/` directory
- [ ] Verify database connection
- [ ] Test database migrations (if any)
- [ ] Set up database backup schedule

### 3. Dependencies

- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Verify all required PHP extensions are installed:
  - [ ] mysqli
  - [ ] mbstring
  - [ ] json
  - [ ] zip (for Excel operations)
  - [ ] gd or imagick (for image processing)

### 4. Security Configuration

- [ ] Verify `.env` file is NOT accessible via web
- [ ] Test `.htaccess` rules are working
- [ ] Verify `.gitignore` excludes sensitive files
- [ ] Change default admin password after first login
- [ ] Review and update CORS settings
- [ ] Enable HTTPS redirect in `.htaccess` (uncomment lines)
- [ ] Verify security headers are being sent
- [ ] Test CSRF protection
- [ ] Review file permissions:
  ```bash
  find . -type f -exec chmod 644 {} \;
  find . -type d -exec chmod 755 {} \;
  chmod 666 php-errors.log
  ```

### 5. Web Server Configuration

#### Apache

- [ ] Enable required modules:
  ```bash
  sudo a2enmod rewrite
  sudo a2enmod headers
  sudo a2enmod ssl
  ```
- [ ] Configure virtual host:
  ```apache
  <VirtualHost *:443>
      ServerName yourdomain.com
      DocumentRoot /path/to/CompleteWareHouse_AJAX_NoIndex_CORS

      <Directory /path/to/CompleteWareHouse_AJAX_NoIndex_CORS>
          AllowOverride All
          Require all granted
      </Directory>

      SSLEngine on
      SSLCertificateFile /path/to/cert.pem
      SSLCertificateKeyFile /path/to/key.pem

      ErrorLog ${APACHE_LOG_DIR}/warehouse_error.log
      CustomLog ${APACHE_LOG_DIR}/warehouse_access.log combined
  </VirtualHost>
  ```
- [ ] Restart Apache: `sudo service apache2 restart`

#### Nginx (Alternative)

- [ ] Configure Nginx server block (see example below)
- [ ] Test configuration: `sudo nginx -t`
- [ ] Reload Nginx: `sudo service nginx reload`

### 6. SSL/TLS Certificate

- [ ] Obtain SSL certificate (Let's Encrypt recommended)
- [ ] Install certificate
- [ ] Verify HTTPS is working
- [ ] Test SSL configuration: https://www.ssllabs.com/ssltest/

### 7. Performance Optimization

- [ ] Enable OPcache in php.ini:
  ```ini
  opcache.enable=1
  opcache.memory_consumption=128
  opcache.interned_strings_buffer=8
  opcache.max_accelerated_files=10000
  opcache.revalidate_freq=2
  ```
- [ ] Configure PHP memory limits
- [ ] Set up caching (if needed)
- [ ] Optimize database indexes
- [ ] Enable compression in web server

### 8. Logging and Monitoring

- [ ] Configure error logging
- [ ] Set up log rotation for `php-errors.log`:
  ```bash
  sudo nano /etc/logrotate.d/warehouse
  ```
  ```
  /path/to/CompleteWareHouse_AJAX_NoIndex_CORS/php-errors.log {
      weekly
      rotate 4
      compress
      missingok
      notifempty
  }
  ```
- [ ] Set up application monitoring
- [ ] Configure uptime monitoring
- [ ] Set up backup monitoring

### 9. Backup Strategy

- [ ] Configure automatic database backups
- [ ] Test backup restoration
- [ ] Set up file system backups
- [ ] Document backup procedures
- [ ] Store backups off-site

### 10. Testing

- [ ] Test user login/logout
- [ ] Test CRUD operations
- [ ] Test import/export functionality
- [ ] Test AJAX requests
- [ ] Verify CORS functionality
- [ ] Test on multiple browsers
- [ ] Test mobile responsiveness
- [ ] Check all error pages (404, 500, etc.)
- [ ] Verify email notifications (if any)
- [ ] Load testing
- [ ] Security testing

## Deployment Steps

### Step 1: Prepare Production Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 php php-mysqli php-mbstring php-zip php-gd mysql-server composer -y

# Secure MySQL
sudo mysql_secure_installation
```

### Step 2: Deploy Application Files

```bash
# Clone repository or upload files
cd /var/www/html
sudo git clone <repository-url> warehouse

# Or using rsync
rsync -avz --exclude='.git' --exclude='vendor' /local/path/ user@server:/var/www/html/warehouse/

# Set ownership
sudo chown -R www-data:www-data warehouse/
```

### Step 3: Configure Application

```bash
cd warehouse

# Copy environment file
cp .env.example .env

# Edit environment file
nano .env

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 666 php-errors.log
```

### Step 4: Database Setup

```bash
# Create database
mysql -u root -p
```

```sql
CREATE DATABASE warehouse_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'warehouse_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON warehouse_prod.* TO 'warehouse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
mysql -u warehouse_user -p warehouse_prod < tables/schema.sql
```

### Step 5: Configure Web Server

See Apache/Nginx configuration above

### Step 6: Enable HTTPS

```bash
# Using Let's Encrypt
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### Step 7: Final Verification

- [ ] Access application via HTTPS
- [ ] Login with admin credentials
- [ ] Change admin password
- [ ] Test key functionality
- [ ] Review logs for errors
- [ ] Monitor server resources

## Post-Deployment

### Immediate Actions

1. Change default admin password
2. Create regular user accounts
3. Remove any test data
4. Verify backups are running
5. Monitor error logs

### Ongoing Maintenance

- Weekly: Review error logs
- Weekly: Check disk space
- Monthly: Review security updates
- Monthly: Test backup restoration
- Quarterly: Security audit
- Yearly: SSL certificate renewal (if not auto-renewed)

## Rollback Plan

In case of deployment failure:

1. Keep previous version backup
2. Document current database state
3. Restore previous application files
4. Restore previous database backup
5. Clear cache and sessions
6. Test functionality

## Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/html/warehouse;
    index index.php;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.(log|txt)$ {
        deny all;
    }

    location ~ ^/(composer\.json|composer\.lock|\.gitignore|README\.md|DEPLOYMENT\.md)$ {
        deny all;
    }

    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # URL Rewriting
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check PHP error logs
   - Verify .htaccess syntax
   - Check file permissions

2. **Database Connection Failed**
   - Verify credentials in .env
   - Check MySQL is running
   - Verify user permissions

3. **CORS Errors**
   - Verify CORS_ALLOWED_ORIGINS in .env
   - Check security headers
   - Review browser console

4. **Session Issues**
   - Check session directory permissions
   - Verify session configuration
   - Clear browser cookies

## Support

For issues during deployment:
1. Check error logs: `tail -f php-errors.log`
2. Review Apache/Nginx logs
3. Check system logs: `sudo journalctl -xe`
4. Consult README.md for additional guidance

## Security Reminders

- Never commit `.env` file to version control
- Use strong database passwords
- Keep software updated
- Regular security audits
- Monitor access logs
- Implement rate limiting (if needed)
- Use fail2ban for SSH protection
