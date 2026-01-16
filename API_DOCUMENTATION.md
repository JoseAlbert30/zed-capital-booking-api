# Booking System Backend API Documentation

## Overview

This is a Laravel-based RESTful API backend for the Modern Booking System. It provides complete booking management, user authentication, payment status tracking, and admin dashboard functionality.

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- SQLite or MySQL

### Installation

1. **Install Dependencies**
   ```bash
   cd booking-backend
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Admin Emails** (Edit `.env` or `config/app.php`)
   ```php
   'admin_emails' => [
       'admin@example.com',
       'jose@example.com',
   ],
   ```

4. **Database Setup**
   ```bash
   php artisan migrate
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000/api`

## API Endpoints

### Authentication Endpoints

#### Register User
- **POST** `/api/auth/register`
- **Body:**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Response:** User object with authentication token

#### Login
- **POST** `/api/auth/login`
- **Body:**
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```
- **Response:** User object with authentication token

#### Get Current User (Protected)
- **GET** `/api/auth/me`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Current user object

#### Logout (Protected)
- **POST** `/api/auth/logout`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Success message

---

### User Endpoints

#### Get User Profile (Protected)
- **GET** `/api/users/profile`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** User profile with bookings

#### Update User Profile (Protected)
- **PUT** `/api/users/profile`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
  ```json
  {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "address": "123 Main St, City"
  }
  ```
- **Response:** Updated user object

#### Get All Users (Admin Only)
- **GET** `/api/users/all`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Response:** Array of all users with booking counts

#### Get User by Email (Admin Only)
- **POST** `/api/users/by-email`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:**
  ```json
  {
    "email": "user@example.com"
  }
  ```
- **Response:** User object with bookings

#### Update User Payment Status (Admin Only)
- **PUT** `/api/users/{user_id}/payment-status`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:**
  ```json
  {
    "payment_status": "fully_paid"
  }
  ```
- **Allowed Values:** `pending`, `partial`, `fully_paid`
- **Response:** Updated user object

#### Regenerate User Password (Admin Only)
- **POST** `/api/users/{user_id}/regenerate-password`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Response:** New temporary password

---

### Booking Endpoints

#### Get All Bookings (Admin Only)
- **GET** `/api/bookings`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Response:** Array of all bookings with user details

#### Get User's Bookings (Protected)
- **GET** `/api/bookings/my-bookings`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Array of user's bookings

#### Create Booking (Protected)
- **POST** `/api/bookings`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
  ```json
  {
    "booking_date": "2026-01-20",
    "time_slot": "14:00",
    "notes": "Optional notes",
    "location": "Office location"
  }
  ```
- **Validation Rules:**
  - `booking_date`: Required, must be after today
  - `time_slot`: Required, must be one of the available slots (09:00-20:00)
  - User must be fully paid and 3 days must have passed since payment date
- **Response:** Created booking object

#### Get Available Time Slots (Protected)
- **GET** `/api/bookings/available-slots?date=2026-01-20`
- **Headers:** `Authorization: Bearer {token}`
- **Query Parameters:** `date` (required, YYYY-MM-DD format)
- **Response:**
  ```json
  {
    "available_slots": ["09:00", "10:00", "14:00", "15:00"]
  }
  ```

#### Get Specific Booking (Protected)
- **GET** `/api/bookings/{booking_id}`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Booking object

#### Update Booking (Admin Only)
- **PUT** `/api/bookings/{booking_id}`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:**
  ```json
  {
    "booking_date": "2026-01-20",
    "time_slot": "15:00",
    "status": "completed",
    "notes": "Updated notes",
    "location": "New location"
  }
  ```
- **Response:** Updated booking object

#### Delete Booking (Protected)
- **DELETE** `/api/bookings/{booking_id}`
- **Headers:** `Authorization: Bearer {token}`
- **Note:** Users can delete their own bookings, admins can delete any booking
- **Response:** Success message

---

### Health Check Endpoints

#### Public Health Check
- **GET** `/api/health`
- **Response:** `{ "status": "ok" }`

#### Protected Health Check
- **GET** `/api/health`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** `{ "status": "ok", "user": {...} }`

---

## Data Models

### User
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "payment_status": "fully_paid",
  "payment_date": "2026-01-08T10:00:00Z",
  "phone": "+1234567890",
  "address": "123 Main St, City",
  "created_at": "2026-01-08T10:00:00Z",
  "updated_at": "2026-01-08T10:00:00Z"
}
```

### Booking
```json
{
  "id": 1,
  "user_id": 1,
  "booking_date": "2026-01-20T14:00:00Z",
  "time_slot": "14:00",
  "status": "confirmed",
  "notes": "Additional notes",
  "location": "Office location",
  "created_at": "2026-01-09T10:00:00Z",
  "updated_at": "2026-01-09T10:00:00Z"
}
```

---

## Payment Status Rules

### Pending
- User hasn't made a payment yet
- Cannot create bookings

### Partial
- User has made an incomplete payment
- Cannot create bookings

### Fully Paid
- User has completed full payment
- Can create bookings after 3 days from payment date
- Can book only from `payment_date + 3 days` onwards

---

## Time Slots

Available booking time slots:
- 09:00, 10:00, 11:00, 12:00, 13:00, 14:00
- 15:00, 16:00, 17:00, 18:00, 19:00, 20:00

---

## Booking Status

- **confirmed**: Booking is confirmed and active
- **completed**: Booking has been completed
- **cancelled**: Booking has been cancelled

---

## Authentication

The API uses Laravel Sanctum for authentication. All protected endpoints require a Bearer token in the Authorization header.

```
Authorization: Bearer {token}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Invalid credentials"
}
```

### 403 Forbidden
```json
{
  "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "message": "User not found"
}
```

### 409 Conflict
```json
{
  "message": "Time slot already booked"
}
```

### 422 Unprocessable Entity
```json
{
  "errors": {
    "email": ["The email must be a valid email address."]
  }
}
```

---

## CORS Configuration

The backend is configured to accept requests from the Next.js frontend. Update `.env` with your frontend URL if needed.

---

## Database Migrations

The following migrations are included:
- `0001_01_01_000000_create_users_table.php` - Base users table
- `0001_01_01_000001_create_cache_table.php` - Cache management
- `0001_01_01_000002_create_jobs_table.php` - Queue jobs
- `2026_01_09_054103_create_bookings_table.php` - Bookings table
- `2026_01_09_054103_add_payment_fields_to_users_table.php` - Payment fields

---

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
│   └── Services/
│       └── BookingService.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── .env.example
└── README.md
```

---

## Development Tips

### Testing with cURL

Register a user:
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

Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Get profile with token:
```bash
curl -X GET http://localhost:8000/api/users/profile \
  -H "Authorization: Bearer {token}"
```

---

## Future Enhancements

- Email notifications for bookings
- SMS reminders
- Payment integration (Stripe, PayPal)
- Booking cancellation policies
- Admin statistics dashboard
- Advanced scheduling rules
- Multiple location support

---

## Support

For issues or questions, contact the development team.
