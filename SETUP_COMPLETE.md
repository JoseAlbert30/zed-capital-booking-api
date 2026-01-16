# Laravel Backend - Setup Complete ✅

Your Laravel backend for the Modern Booking System has been successfully created!

## Directory Location
```
/Users/webdeveloper/Downloads/Jose Backup/zedcapital.booking/booking-backend/
```

## What's Been Created

### 1. **Core Application**
- ✅ Complete Laravel 12 project structure
- ✅ Database models (User, Booking)
- ✅ API controllers (Auth, Booking, User)
- ✅ Business logic service (BookingService)
- ✅ Database migrations
- ✅ API routes configuration

### 2. **Authentication System**
- Registration and login endpoints
- Bearer token authentication (Laravel Sanctum)
- Protected routes with token validation
- Logout functionality

### 3. **Booking Management**
- Create, read, update, delete bookings
- Time slot availability checking
- Payment eligibility validation
- Admin booking management
- Booking status tracking

### 4. **User Management**
- User profiles with contact information
- Payment status tracking (pending, partial, fully_paid)
- Payment date tracking
- Admin user management
- Admin password reset functionality

### 5. **Admin Features**
- View all users and bookings
- Update user payment status
- Regenerate user passwords
- Search users by email
- Modify booking details
- Access control based on admin email list

## Getting Started

### 1. Navigate to Backend
```bash
cd "/Users/webdeveloper/Downloads/Jose Backup/zedcapital.booking/booking-backend"
```

### 2. Install Dependencies (if not already done)
```bash
composer install
```

### 3. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
php artisan migrate
```

### 5. Configure Admin Emails
Edit `config/app.php` and update the admin_emails array:
```php
'admin_emails' => [
    'admin@example.com',
    'jose@example.com',  // Add your email here
],
```

### 6. Start Server
```bash
php artisan serve
```

The API will be available at: `http://localhost:8000/api`

## API Endpoints Summary

### Authentication (Public)
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login

### Authentication (Protected)
- `GET /api/auth/me` - Get current user
- `POST /api/auth/logout` - Logout user

### User Endpoints (Protected)
- `GET /api/users/profile` - Get user profile
- `PUT /api/users/profile` - Update profile
- `GET /api/users/all` - Get all users (Admin only)
- `PUT /api/users/{id}/payment-status` - Update payment (Admin only)
- `POST /api/users/{id}/regenerate-password` - Reset password (Admin only)

### Booking Endpoints (Protected)
- `POST /api/bookings` - Create booking
- `GET /api/bookings/my-bookings` - Get user's bookings
- `GET /api/bookings` - Get all bookings (Admin only)
- `GET /api/bookings/available-slots?date=YYYY-MM-DD` - Check availability
- `PUT /api/bookings/{id}` - Update booking (Admin only)
- `DELETE /api/bookings/{id}` - Delete booking

## Test Registration

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

## Test Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

## Connect to Next.js Frontend

Update your Next.js environment variables to point to the backend:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

Then in your frontend code, use:

```javascript
const token = localStorage.getItem('authToken');
const response = await fetch('http://localhost:8000/api/users/profile', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

## Database Structure

### Users Table
- id (Primary Key)
- name
- email (Unique)
- password (Hashed)
- payment_status (pending, partial, fully_paid)
- payment_date (Nullable)
- phone (Nullable)
- address (Nullable)
- email_verified_at (Nullable)
- created_at, updated_at

### Bookings Table
- id (Primary Key)
- user_id (Foreign Key)
- booking_date (DateTime)
- time_slot (e.g., "14:00")
- status (confirmed, completed, cancelled)
- notes (Nullable)
- location (Nullable)
- created_at, updated_at

### Personal Access Tokens Table (Sanctum)
- id (Primary Key)
- tokenable_id
- tokenable_type
- name
- token (Hashed)
- abilities (JSON)
- last_used_at (Nullable)
- created_at, updated_at

## Key Features

✅ **User Authentication**
- Secure password hashing with bcrypt
- Token-based API authentication
- Token expiration support

✅ **Payment Management**
- Track payment status for each user
- Enforce 3-day waiting period after payment
- Admin can update payment status

✅ **Booking System**
- Prevent double-booking of time slots
- Check payment status before booking
- Support for booking notes and location
- Admin can modify/cancel any booking

✅ **Authorization**
- Role-based access control
- Users can only access their own data
- Admin-only protected endpoints

✅ **Error Handling**
- Comprehensive validation
- Meaningful error messages
- Proper HTTP status codes

## Useful Commands

```bash
# List all routes
php artisan route:list

# Database management
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Reset database
php artisan tinker              # Interactive shell

# Caching
php artisan cache:clear
php artisan config:cache

# Testing endpoints
php artisan serve               # Start development server
```

## Documentation Files

- **API_DOCUMENTATION.md** - Complete API reference with examples
- **README_BACKEND.md** - Quick start guide and overview

## Production Checklist

Before deploying to production:

- [ ] Set `APP_DEBUG=false` in .env
- [ ] Use MySQL/PostgreSQL instead of SQLite
- [ ] Set strong `APP_KEY`
- [ ] Configure HTTPS
- [ ] Update admin emails in config/app.php
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan migrate --force`
- [ ] Configure CORS for frontend domain
- [ ] Set up proper logging
- [ ] Enable rate limiting
- [ ] Configure email notifications

## File Structure

```
booking-backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── BookingController.php
│   │       └── UserController.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Booking.php
│   └── Services/
│       └── BookingService.php
├── config/
│   ├── app.php                      # Admin emails configured here
│   ├── sanctum.php
│   └── ... (other config files)
├── database/
│   └── migrations/
│       ├── 2026_01_09_054103_create_bookings_table.php
│       ├── 2026_01_09_054103_add_payment_fields_to_users_table.php
│       └── ... (other migrations)
├── routes/
│   └── api.php                      # API routes
├── .env                             # Environment configuration
├── .env.example
├── API_DOCUMENTATION.md             # Full API docs
├── README_BACKEND.md                # Setup guide
├── composer.json
├── composer.lock
└── artisan                          # Laravel CLI tool
```

## Next Steps

1. **Configure Admin Emails** - Update config/app.php with your admin email addresses
2. **Start the Server** - Run `php artisan serve`
3. **Test Endpoints** - Use the curl examples above
4. **Connect Frontend** - Update your Next.js environment variables
5. **Deploy** - Follow the production checklist

## Support & Troubleshooting

### Issue: Port 8000 already in use
```bash
# Use different port
php artisan serve --port=8001
```

### Issue: Database locked
```bash
# Reset database
php artisan migrate:fresh
```

### Issue: Token not working
```bash
# Check token in personal_access_tokens table
php artisan tinker
> PersonalAccessToken::all()
```

## Integration Notes

This backend is designed to work seamlessly with your Next.js frontend:
- Handles authentication with Bearer tokens
- Returns JSON responses compatible with JavaScript
- Supports CORS for frontend requests
- Provides all endpoints needed by your booking system components

---

**Backend Ready!** 🚀

Your Laravel booking system backend is now ready to use. Start the server and connect it to your Next.js frontend.

For detailed API documentation, see: `API_DOCUMENTATION.md`
