# Zed Capital Booking API

Laravel backend API for Viera Residences handover booking and management system.

## 🚀 Features

- **User Management**: Multi-owner unit management with primary owner designation
- **Property & Unit Management**: Hierarchical property-unit structure with detailed tracking
- **Booking System**: Appointment scheduling with calendar integration
- **Handover Workflow**: Complete handover process with document management
- **Email System**: Automated email notifications (booking confirmations, handover notices, etc.)
- **File Management**: Document uploads (SOA, handover documents, photos, signatures)
- **Timeline Tracking**: Comprehensive audit trail with remarks and admin attribution
- **Magic Link Authentication**: Secure passwordless login for clients

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0 or MariaDB >= 10.3
- **Node.js & npm** (for compiling assets)
- **Git**

### Recommended Tools
- **phpMyAdmin** or **MySQL Workbench** for database management
- **Mailtrap** or **Gmail** for email testing
- **Postman** for API testing

## 🛠️ Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/JoseAlbert30/zed-capital-booking-api.git
cd zed-capital-booking-api
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file with your settings:

```env
# Application
APP_NAME="Zed Capital Booking"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zed_booking
DB_USERNAME=root
DB_PASSWORD=your_password

# Mail Configuration (Example with Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@zedcapital.ae"
MAIL_FROM_NAME="${APP_NAME}"

# Or use Gmail
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.gmail.com
# MAIL_PORT=587
# MAIL_USERNAME=your_email@gmail.com
# MAIL_PASSWORD=your_app_password
# MAIL_ENCRYPTION=tls

# Queue Configuration
QUEUE_CONNECTION=database

# Frontend URL (for CORS and email links)
FRONTEND_URL=http://localhost:3000
```

### 5. Create Database

Create a new MySQL database:

```sql
CREATE DATABASE zed_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or using command line:

```bash
mysql -u root -p -e "CREATE DATABASE zed_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Seed Database with Sample Data

```bash
php artisan db:seed
```

This will create:
- 1 Admin user
- 1 Property (Viera Residences)
- Sample units
- Sample users/owners
- Test bookings

**Default Admin Credentials:**
- Email: `admin@zedcapital.ae`
- Password: `password`

### 8. Create Storage Link

Link the public storage directory:

```bash
php artisan storage:link
```

### 9. Set File Permissions

Ensure storage and cache directories are writable:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # On Linux
```

On macOS/development:
```bash
chmod -R 777 storage bootstrap/cache
```

### 10. Install NPM Dependencies (Optional)

If you need to compile frontend assets:

```bash
npm install
npm run dev
```

## 🚀 Running the Application

### Start Development Server

```bash
php artisan serve
```

The API will be available at: `http://localhost:8000`

### Start Queue Worker (Required for Emails)

In a separate terminal:

```bash
php artisan queue:work
```

Or for development with auto-reload:

```bash
php artisan queue:listen
```

## 📁 Important Directories

### Storage Structure

```
storage/app/public/
├── attachments/                    # User-uploaded files (ignored by Git)
│   └── {project_name}/
│       └── {unit_no}/
│           ├── soa_*.pdf
│           ├── payment_proof_*.pdf
│           └── ...
├── handover-notice-attachments/    # Handover email PDFs (tracked by Git)
│   └── viera-residences/
│       ├── Utilities Registration Guide.pdf
│       └── Viera Residences - Escrow Acc.pdf
└── templates/                      # Handover templates (tracked by Git)
    ├── declaration_v3.pdf
    └── handover_checklist_v2.pdf
```

## 📧 Email Configuration

### Using Mailtrap (Development)

1. Create account at https://mailtrap.io
2. Copy SMTP credentials from your inbox
3. Update `.env` with Mailtrap credentials

### Using Gmail (Production)

1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_16_digit_app_password
MAIL_ENCRYPTION=tls
```

## 🔑 API Endpoints

### Authentication
- `POST /api/login` - Admin login
- `POST /api/magic-link` - Generate magic link for client

### Properties & Units
- `GET /api/properties` - List all properties
- `GET /api/units` - List all units (with filters)
- `GET /api/units/{id}` - Get unit details
- `PUT /api/units/{id}/payment-status` - Update payment status
- `POST /api/units/{id}/send-handover-email` - Send initial handover notice

### Bookings
- `GET /api/bookings` - List all bookings
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Cancel booking
- `POST /api/bookings/{id}/upload-handover-file` - Upload handover documents
- `POST /api/bookings/{id}/complete-handover` - Complete handover process

### Users
- `GET /api/users` - List all users
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}/payment-status` - Update payment status

See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete API reference.

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

## 📝 Additional Documentation

- [API Documentation](API_DOCUMENTATION.md)
- [Database Reference](DATABASE_REFERENCE.md)
- [Email Logs Reference](EMAIL_LOGS_REFERENCE.md)
- [Handover Requirements](HANDOVER_REQUIREMENTS.md)
- [Timeline Logging Guide](TIMELINE_LOGGING_GUIDE.md)

## 🔧 Troubleshooting

### Database Connection Failed
- Verify MySQL is running: `sudo systemctl status mysql` (Linux) or check Activity Monitor (Mac)
- Check database credentials in `.env`
- Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Storage Link Not Working
```bash
rm public/storage  # Remove existing link
php artisan storage:link
```

### Emails Not Sending
- Check queue worker is running: `php artisan queue:work`
- Verify email credentials in `.env`
- Check `storage/logs/laravel.log` for errors
- Test email configuration: Create a test booking

### Permission Denied Errors
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Port 8000 Already in Use
```bash
php artisan serve --port=8001
```

## 🚀 Production Deployment

### 1. Environment Setup
```bash
APP_ENV=production
APP_DEBUG=false
```

### 2. Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 3. Set Proper Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Configure Web Server (Apache/Nginx)
Point document root to `/public` directory

### 5. Setup Supervisor for Queue Worker
```ini
[program:booking-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/booking-backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/booking-backend/storage/logs/worker.log
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software developed for Zed Capital Real Estate.

## 📧 Support

For support, email: vantage@zedcapital.ae

---

**Built with Laravel 11** | **Powered by Zed Capital**
