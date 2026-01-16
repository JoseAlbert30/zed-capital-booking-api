# Database - Complete Reference

## Database Type: MySQL

Your Laravel backend is configured to use **MySQL** for data persistence.

## Configuration

### Current .env Settings
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=root
DB_PASSWORD=
```

- **Connection Type:** MySQL
- **Server Address:** 127.0.0.1 (localhost)
- **Port:** 3306 (default MySQL port)
- **Database Name:** `booking_system`
- **Username:** `root` (default MySQL admin)
- **Password:** (empty - typical for local development)

## Tables Overview

### 1. **users** Table
Stores user account information and payment status.

```
id          | BIGINT      | Primary Key, Auto-increment
name        | VARCHAR(255)| User's full name
email       | VARCHAR(255)| Unique email address
email_verified_at | TIMESTAMP | Email verification timestamp (nullable)
password    | VARCHAR(255)| Hashed password (bcrypt)
payment_status | ENUM    | 'pending', 'partial', or 'fully_paid'
payment_date   | TIMESTAMP | When payment was completed (nullable)
phone       | VARCHAR(20) | User's phone number (nullable)
address     | TEXT        | User's address (nullable)
remember_token | VARCHAR(100) | Token for "remember me" (nullable)
created_at  | TIMESTAMP   | Account creation time
updated_at  | TIMESTAMP   | Last update time

Indexes:
- email (unique)
- payment_status
```

**Purpose:** Authentication, user management, payment tracking

**Sample Record:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "payment_status": "fully_paid",
  "payment_date": "2026-01-08 10:30:00",
  "phone": "+1234567890",
  "address": "123 Main St, City"
}
```

### 2. **bookings** Table
Stores all booking records linked to users.

```
id          | BIGINT      | Primary Key, Auto-increment
user_id     | BIGINT      | Foreign Key to users table
booking_date| TIMESTAMP   | Date and time of booking
time_slot   | VARCHAR(5)  | Time slot (e.g., "14:00")
status      | ENUM        | 'confirmed', 'completed', or 'cancelled'
notes       | TEXT        | Optional booking notes (nullable)
location    | VARCHAR(255)| Booking location (nullable)
created_at  | TIMESTAMP   | Booking creation time
updated_at  | TIMESTAMP   | Last update time

Foreign Keys:
- user_id → users(id) ON DELETE CASCADE

Indexes:
- user_id
- booking_date
- status
```

**Purpose:** Booking management, scheduling

**Sample Record:**
```json
{
  "id": 1,
  "user_id": 1,
  "booking_date": "2026-01-20 14:00:00",
  "time_slot": "14:00",
  "status": "confirmed",
  "notes": "Handover appointment",
  "location": "Office A"
}
```

### 3. **personal_access_tokens** Table
Manages API authentication tokens (Laravel Sanctum).

```
id          | BIGINT      | Primary Key, Auto-increment
tokenable_type | VARCHAR(255) | Model type (e.g., "App\Models\User")
tokenable_id   | BIGINT      | User ID
name        | VARCHAR(255)| Token name (e.g., "auth_token")
token       | VARCHAR(80) | Hashed token (unique)
abilities   | JSON        | Token permissions
last_used_at | TIMESTAMP  | When token was last used (nullable)
expires_at  | TIMESTAMP   | Token expiration time (nullable)
created_at  | TIMESTAMP   | Token creation time
updated_at  | TIMESTAMP   | Last update time

Indexes:
- tokenable (type, id)
- token (unique)
```

**Purpose:** API authentication and authorization

**Sample Record:**
```json
{
  "id": 1,
  "tokenable_type": "App\\Models\\User",
  "tokenable_id": 1,
  "name": "auth_token",
  "token": "a1b2c3d4e5f6g7h8i9j0...",
  "abilities": ["*"],
  "last_used_at": "2026-01-09 15:30:00"
}
```

### 4. **cache** Table
Stores cached data for performance.

```
key        | VARCHAR(255)| Primary Key (cache key)
value      | LONGTEXT    | Cached value
expiration | INT         | Expiration timestamp
```

**Purpose:** Performance optimization through caching

### 5. **jobs** Table
Stores queued jobs for async processing.

```
id        | BIGINT      | Primary Key
queue     | VARCHAR(255)| Queue name
payload   | LONGTEXT    | Job data (JSON)
attempts  | TINYINT     | Number of attempts
reserved_at | BIGINT    | Reservation time (nullable)
available_at | BIGINT   | When job becomes available
created_at | BIGINT     | Creation timestamp
```

**Purpose:** Job queue management for background tasks

### 6. **job_batches** Table
Manages batch job processing.

```
id        | VARCHAR(255)| Primary Key
name      | VARCHAR(255)| Batch name
total_jobs | INT        | Total jobs in batch
pending_jobs | INT      | Pending jobs
failed_jobs | INT       | Failed jobs
failed_job_ids | LONGTEXT| Failed job IDs
options   | LONGTEXT    | Batch options (JSON)
cancelled_at | TIMESTAMP| Cancellation time (nullable)
created_at | TIMESTAMP  | Creation time
finished_at | TIMESTAMP | Completion time (nullable)
```

**Purpose:** Batch job processing

## Relationships

### User → Bookings (One-to-Many)
- One user can have many bookings
- When user is deleted, their bookings are cascade deleted
- Access: `$user->bookings()`

### Booking → User (Many-to-One)
- Many bookings belong to one user
- Access: `$booking->user()`

## Database Queries

### Get User with All Bookings
```sql
SELECT u.*, b.* 
FROM users u
LEFT JOIN bookings b ON u.id = b.user_id
WHERE u.id = 1;
```

### Count Bookings by Payment Status
```sql
SELECT 
    u.payment_status,
    COUNT(b.id) as booking_count
FROM users u
LEFT JOIN bookings b ON u.id = b.user_id
GROUP BY u.payment_status;
```

### Available Time Slots for Date
```sql
SELECT DISTINCT time_slot 
FROM bookings 
WHERE DATE(booking_date) = '2026-01-20' 
  AND status != 'cancelled'
ORDER BY time_slot;
```

### Users Eligible to Book (3+ days after payment)
```sql
SELECT * 
FROM users 
WHERE payment_status = 'fully_paid' 
  AND payment_date <= DATE_SUB(NOW(), INTERVAL 3 DAY);
```

### Upcoming Bookings (Next 7 Days)
```sql
SELECT u.name, b.* 
FROM bookings b
JOIN users u ON b.user_id = u.id
WHERE booking_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
  AND b.status != 'cancelled'
ORDER BY b.booking_date;
```

### Completed Bookings by User
```sql
SELECT COUNT(*) as completed_bookings
FROM bookings
WHERE user_id = 1 AND status = 'completed';
```

## Migrations

### Migration Files (Auto-created)

1. **0001_01_01_000000_create_users_table.php**
   - Creates users table
   - Sets up authentication fields

2. **0001_01_01_000001_create_cache_table.php**
   - Creates cache table for performance

3. **0001_01_01_000002_create_jobs_table.php**
   - Creates jobs table for queue management

4. **2026_01_09_054103_create_bookings_table.php**
   - Creates bookings table with proper indexes
   - Sets up foreign key to users

5. **2026_01_09_054103_add_payment_fields_to_users_table.php**
   - Adds payment_status, payment_date, phone, address columns
   - Creates payment_status index

6. **2026_01_09_054412_create_personal_access_tokens_table.php**
   - Creates tokens table for API authentication
   - Sets up required indexes

## Setup Instructions

### 1. Install MySQL
**macOS:**
```bash
brew install mysql
brew services start mysql
```

**Linux:**
```bash
sudo apt-get install mysql-server
sudo systemctl start mysql
```

**Windows:**
Download from https://dev.mysql.com/downloads/mysql/

### 2. Create Database
```bash
mysql -u root -e "CREATE DATABASE booking_system;"
```

### 3. Run Migrations
```bash
cd booking-backend
php artisan migrate
```

### 4. Verify Setup
```bash
php artisan db
```

## Backup & Restore

### Backup
```bash
mysqldump -u root booking_system > backup.sql
```

### Restore
```bash
mysql -u root booking_system < backup.sql
```

## Performance Tips

✅ **Indexes Created Automatically**
- user_id on bookings
- booking_date on bookings
- payment_status on users
- email on users

✅ **Connection Pooling**
- Laravel handles connection pooling
- Default 25 concurrent connections

✅ **Query Optimization**
- Use eager loading to prevent N+1 queries
- Example: `User::with('bookings')->get()`

## Environment Configuration

### For Local Development
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=root
DB_PASSWORD=
```

### For Production
```env
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=booking_app
DB_PASSWORD=SecurePassword123!
```

## Troubleshooting

### Database Connection Error
**Problem:** `SQLSTATE[HY000]: General error: 1030`
**Solution:** Check MySQL is running
```bash
brew services start mysql
```

### Database Not Found
**Problem:** `Unknown database 'booking_system'`
**Solution:** Create database
```bash
mysql -u root -e "CREATE DATABASE booking_system;"
```

### Access Denied
**Problem:** `Access denied for user 'root'@'localhost'`
**Solution:** Check credentials in .env
```env
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

### Test Connection
```bash
php artisan db
```

## Data Retention

### Auto-Deletion Rules
- Bookings: Deleted when user is deleted (cascade)
- Personal Access Tokens: Deleted when user is deleted

### Backup Strategy
- Daily automatic backups (production)
- Weekly manual backups (local)
- Test restore procedures regularly

## Statistics

After setup, your database will have:

| Resource | Count |
|----------|-------|
| Tables | 6 |
| Indexes | 12+ |
| Foreign Keys | 1 |
| Views | 0 |

## Summary

✅ **Database Type:** MySQL
✅ **Default Database:** booking_system
✅ **6 Tables** with proper relationships
✅ **Automatic Migrations** for setup
✅ **Indexes** for performance
✅ **Foreign Keys** for data integrity
✅ **Timestamps** for audit trails
✅ **JSON** support for advanced data

Your database is ready for production use! 🚀

For complete MySQL setup steps, see: [MYSQL_SETUP.md](./MYSQL_SETUP.md)
