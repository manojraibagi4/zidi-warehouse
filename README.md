# Warehouse Management System

A modern, secure warehouse inventory management system built with PHP.

## Features

- Inventory Management
- User Authentication & Authorization
- Import/Export functionality (Excel, CSV, PDF)
- Multi-language support
- AJAX-based operations
- CORS support for API access
- Responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Composer

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd CompleteWareHouse_AJAX_NoIndex_CORS
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Copy the example environment file and update it with your configuration:

```bash
cp .env.example .env
```

Edit `.env` file and update the following variables:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=your_database_user
DB_PASSWORD=your_database_password
DB_NAME=your_database_name

# Application Environment
APP_ENV=production
APP_DEBUG=false

# CORS Configuration (update with your actual domain)
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

### 4. Create database

Create a MySQL database and import the schema:

```bash
mysql -u your_user -p your_database < tables/schema.sql
```

### 5. Set permissions

Ensure the web server has write permissions to:

```bash
chmod 755 src/
chmod 666 php-errors.log
```

### 6. Configure Apache

Ensure your Apache configuration allows `.htaccess` overrides:

```apache
<Directory /path/to/CompleteWareHouse_AJAX_NoIndex_CORS>
    AllowOverride All
</Directory>
```

Restart Apache:

```bash
sudo service apache2 restart
```

## Production Deployment

### Security Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Update database credentials in `.env`
- [ ] Update `CORS_ALLOWED_ORIGINS` with your actual domain(s)
- [ ] Enable HTTPS and update allowed origins to use `https://`
- [ ] Ensure `.env` file is NOT accessible via web (already protected by `.htaccess`)
- [ ] Set proper file permissions (files: 644, directories: 755)
- [ ] Disable directory listing in Apache
- [ ] Review and update Content Security Policy in `src/config/security.php`
- [ ] Set up regular database backups
- [ ] Configure log rotation for `php-errors.log`
- [ ] Review and limit user permissions in the application

### Environment Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `APP_ENV` | Application environment (development/production) | production | Yes |
| `APP_DEBUG` | Enable debug mode | false | No |
| `DB_HOST` | Database host | localhost | Yes |
| `DB_USER` | Database username | - | Yes |
| `DB_PASSWORD` | Database password | - | Yes |
| `DB_NAME` | Database name | - | Yes |
| `CORS_ENABLED` | Enable CORS | true | No |
| `CORS_ALLOWED_ORIGINS` | Comma-separated list of allowed origins | - | Yes (production) |
| `CORS_STRICT_VALIDATION` | Enable strict CORS validation | true | No |
| `SESSION_LIFETIME` | Session lifetime in seconds | 3600 | No |
| `DEFAULT_LANGUAGE` | Default language (en/de) | en | No |

## Development

For development, you can use the default `.env` settings:

```env
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=modernwarehouse
```

## Usage

### Default Login

After installation, an admin user is created automatically:

- Username: `admin`
- Password: `admin123`

**IMPORTANT:** Change the admin password immediately after first login!

### API Endpoints

The application supports AJAX requests for various operations:

- `POST /store` - Create new item
- `POST /update` - Update existing item
- `POST /delete` - Delete item
- `POST /search_article` - Search for items
- `GET /export_excel` - Export to Excel
- `GET /export_csv` - Export to CSV
- `GET /export_pdf` - Export to PDF
- `POST /import_file` - Import from file

## Security Features

- CSRF protection on all forms
- XSS protection headers
- SQL injection prevention using prepared statements
- Session security (HttpOnly, Secure cookies in production)
- CORS validation
- Input sanitization
- Secure password hashing
- HTTPS enforcement in production
- Content Security Policy headers

## File Structure

```
├── includes/           # Header, footer, language files
├── public/            # Public assets (CSS, JS, images)
├── src/
│   ├── config/        # Configuration files
│   ├── controllers/   # Application controllers
│   ├── models/        # Data models
│   ├── views/         # View templates
│   ├── export/        # Export functionality
│   └── import/        # Import functionality
├── tables/            # Database schema
├── vendor/            # Composer dependencies
├── .env               # Environment configuration (NOT in git)
├── .env.example       # Example environment file
├── .gitignore         # Git ignore rules
├── .htaccess          # Apache rewrite rules
├── composer.json      # PHP dependencies
└── index.php          # Application entry point
```

## Troubleshooting

### Database Connection Error

- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check database exists and user has proper permissions

### CORS Errors

- Verify `CORS_ALLOWED_ORIGINS` includes your frontend domain
- Check that `APP_ENV` is set correctly
- Review browser console for specific CORS errors

### Session Issues

- Clear browser cookies
- Verify session directory is writable
- Check `SESSION_LIFETIME` setting

### .htaccess Not Working

- Enable mod_rewrite: `sudo a2enmod rewrite`
- Verify AllowOverride is set to All
- Restart Apache

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

[Your License Here]

## Support

For support, please open an issue in the repository.
