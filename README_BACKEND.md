# Booking System - Laravel Backend

A complete Laravel REST API backend for the Modern Booking System Next.js application. Handles user authentication, booking management, payment status tracking, and admin operations.

## Features

✅ **User Authentication**
- Registration and login with Laravel Sanctum tokens
- Secure password hashing
- Token-based API authentication

✅ **Booking Management**
- Create, read, update, delete bookings
- Time slot availability checking
- Payment eligibility validation
- Booking status tracking (confirmed, completed, cancelled)

✅ **Payment Tracking**
- Payment status management (pending, partial, fully_paid)
- 3-day booking window after payment
- Admin payment status updates

✅ **Admin Dashboard**
- View all users and bookings
- Update user payment status
- Reset user passwords
- Modify booking details
- Search users by email

✅ **Authorization**
- Role-based access control (Admin vs User)
- User can only view/modify their own bookings
- Admin-only endpoints for system management

## Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js (for frontend)

### Installation

1. **Navigate to backend directory**
   ```bash
   cd booking-backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Configure admin emails** (edit config/app.php)
   ```php
   'admin_emails' => [
       'admin@example.com',
       'jose@example.com',
   ],
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

Server runs at `http://localhost:8000`

## Project Structure

```
booking-backend/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── BookingController.php
│   │   └── UserController.php
│   ├── Models/
│   │   ├── Booking.php
│   │   └── User.php
│   ├── Services/
│   │   └── BookingService.php
├── database/migrations/
├── routes/api.php
├── API_DOCUMENTATION.md
└── README_BACKEND.md
```

## Key Endpoints

### Auth
- `POST /api/auth/register` - Register
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Get current user
- `POST /api/auth/logout` - Logout

### Users
- `GET /api/users/profile` - Get profile
- `PUT /api/users/profile` - Update profile
- `GET /api/users/all` - All users (Admin)
- `PUT /api/users/{id}/payment-status` - Update payment (Admin)

### Bookings
- `POST /api/bookings` - Create booking
- `GET /api/bookings/my-bookings` - User's bookings
- `GET /api/bookings` - All bookings (Admin)
- `GET /api/bookings/available-slots?date=YYYY-MM-DD` - Available slots
- `PUT /api/bookings/{id}` - Update (Admin)
- `DELETE /api/bookings/{id}` - Delete

**Full documentation**: See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

## Database

**MySQL** is configured by default in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=root
DB_PASSWORD=
```

### Database Setup

1. **Create the database:**
   ```bash
   mysql -u root -e "CREATE DATABASE booking_system;"
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate
   ```

For complete MySQL setup guide, see: [MYSQL_SETUP.md](./MYSQL_SETUP.md)

## Booking Rules

1. User must have `fully_paid` payment status
2. Must wait 3+ days after payment to book
3. Cannot book in the past
4. Each time slot (09:00-20:00) can only have one booking per day
5. Admins can override all rules

## Integration with Next.js

Frontend authenticates and communicates with this API. Token should be included in requests:

```
Authorization: Bearer {token}
```

## Testing Endpoints

**Register:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"User","email":"user@test.com","password":"password123","password_confirmation":"password123"}'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"password123"}'
```

**Protected endpoint:**
```bash
curl -X GET http://localhost:8000/api/users/profile \
  -H "Authorization: Bearer {YOUR_TOKEN}"
```

## Development Commands

```bash
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Reset database
php artisan tinker           # Interactive shell
php artisan route:list       # View all routes
php artisan cache:clear      # Clear cache
```

## Production Deployment

1. Set `APP_DEBUG=false` in .env
2. Use MySQL/PostgreSQL instead of SQLite
3. Run migrations: `php artisan migrate --force`
4. Configure proper CORS origins
5. Enable HTTPS
6. Use strong APP_KEY

## Support

For issues or questions, refer to API_DOCUMENTATION.md or contact the development team.

---
**Version**: 1.0.0 | **Last Updated**: January 9, 2026
