# MySQL Database Setup Guide

## Overview

Your Laravel backend is configured to use **MySQL** instead of SQLite. This guide covers everything you need to know about the database setup.

## Current Configuration

Your `.env` file is already configured for MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_system
DB_USERNAME=root
DB_PASSWORD=
```

## Prerequisites

### Install MySQL

#### macOS (using Homebrew)
```bash
# Install MySQL
brew install mysql

# Start MySQL service
brew services start mysql

# Verify installation
mysql --version
```

#### Windows
1. Download from: https://dev.mysql.com/downloads/mysql/
2. Run installer and follow setup wizard
3. During setup, set root password (or leave blank)

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install mysql-server

# Start MySQL
sudo systemctl start mysql

# Verify
mysql --version
```

## Setup Steps

### 1. Create Database

**Option A: Using MySQL CLI**

```bash
# Access MySQL as root
mysql -u root

# Then run these commands in MySQL:
CREATE DATABASE booking_system;
EXIT;
```

**Option B: Using MySQL Workbench (GUI)**
1. Open MySQL Workbench
2. Click "+" to create new connection
3. Create schema named `booking_system`

**Option C: Using Terminal (One-liner)**
```bash
mysql -u root -e "CREATE DATABASE booking_system;"
```

### 2. Update .env (if needed)

Edit `.env` file with your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1          # MySQL server address
DB_PORT=3306               # MySQL port (default)
DB_DATABASE=booking_system # Database name
DB_USERNAME=root           # MySQL username
DB_PASSWORD=               # MySQL password (empty if no password set)
```

### 3. Run Migrations

```bash
cd booking-backend

# Run all migrations to create tables
php artisan migrate
```

You should see output like:
```
Running migrations:
0001_01_01_000000_create_users_table ..................... 45.32ms DONE
0001_01_01_000001_create_cache_table ..................... 12.18ms DONE
0001_01_01_000002_create_jobs_table ....................... 38.45ms DONE
2026_01_09_054103_add_payment_fields_to_users_table ....... 22.15ms DONE
2026_01_09_054103_create_bookings_table ................... 31.67ms DONE
2026_01_09_054412_create_personal_access_tokens_table .... 28.93ms DONE
```

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    payment_status ENUM('pending', 'partial', 'fully_paid') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_payment_status (payment_status)
);
```

**Columns:**
- `id` - Unique user identifier
- `name` - User's full name
- `email` - User's email address
- `password` - Hashed password (bcrypt)
- `payment_status` - One of: pending, partial, fully_paid
- `payment_date` - When payment was completed
- `phone` - User's phone number
- `address` - User's address
- `created_at` - Account creation timestamp
- `updated_at` - Last update timestamp

### Bookings Table

```sql
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    booking_date TIMESTAMP NOT NULL,
    time_slot VARCHAR(5) NOT NULL,
    status ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    notes TEXT NULL,
    location VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status)
);
```

**Columns:**
- `id` - Unique booking identifier
- `user_id` - Reference to user (foreign key)
- `booking_date` - Date and time of booking
- `time_slot` - Time slot (e.g., "14:00")
- `status` - One of: confirmed, completed, cancelled
- `notes` - Optional booking notes
- `location` - Booking location
- `created_at` - Booking creation timestamp
- `updated_at` - Last update timestamp

### Personal Access Tokens Table

```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(80) NOT NULL UNIQUE,
    abilities JSON NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
);
```

**Used by Laravel Sanctum for API authentication**

### Cache Table

```sql
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value LONGTEXT NOT NULL,
    expiration INT NOT NULL,
    
    INDEX idx_expiration (expiration)
);
```

### Jobs Table

```sql
CREATE TABLE jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    reserved_at BIGINT UNSIGNED NULL,
    available_at BIGINT UNSIGNED NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    
    INDEX idx_queue (queue),
    INDEX idx_reserved_at (reserved_at),
    INDEX idx_available_at (available_at)
);
```

## Useful MySQL Commands

### Check if MySQL is Running
```bash
# macOS
brew services list | grep mysql

# Linux
sudo systemctl status mysql

# Windows - Should be in Services
```

### Access MySQL CLI
```bash
mysql -u root

# Or with password
mysql -u root -p
```

### View Database
```bash
# In MySQL CLI:

# Show all databases
SHOW DATABASES;

# Use specific database
USE booking_system;

# Show all tables
SHOW TABLES;

# Show table structure
DESCRIBE users;
DESCRIBE bookings;

# Count records
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM bookings;

# Exit
EXIT;
```

### Common MySQL Queries

**View all users:**
```sql
SELECT id, name, email, payment_status, payment_date FROM users;
```

**View all bookings:**
```sql
SELECT b.id, b.user_id, u.name, b.booking_date, b.time_slot, b.status 
FROM bookings b
JOIN users u ON b.user_id = u.id
ORDER BY b.booking_date;
```

**Count bookings by date:**
```sql
SELECT DATE(booking_date) as date, COUNT(*) as count 
FROM bookings 
WHERE status != 'cancelled'
GROUP BY DATE(booking_date);
```

**Check available time slots:**
```sql
SELECT DISTINCT time_slot 
FROM bookings 
WHERE DATE(booking_date) = '2026-01-20' 
AND status != 'cancelled';
```

## Laravel Migration Commands

### Run Migrations
```bash
# Run all pending migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=database/migrations/2026_01_09_054103_create_bookings_table.php

# Show migration status
php artisan migrate:status
```

### Rollback Migrations
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Rollback and re-run (CAUTION: deletes all data)
php artisan migrate:refresh

# Refresh and seed
php artisan migrate:refresh --seed
```

### Reset Database (CAUTION: Deletes Everything)
```bash
# Fresh migration (equivalent to migrate:reset + migrate)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed
```

## Troubleshooting

### Error: "SQLSTATE[HY000]: General error: 1030 Got error -1 from storage engine"
**Solution:** Check that MySQL is running and database exists

```bash
# Start MySQL
brew services start mysql

# Create database if missing
mysql -u root -e "CREATE DATABASE booking_system;"
```

### Error: "SQLSTATE[HY000] [2002] No such file or directory"
**Solution:** MySQL is not running

```bash
# macOS
brew services start mysql

# Linux
sudo systemctl start mysql

# Windows
# Check Services and ensure MySQL is running
```

### Error: "Access denied for user 'root'@'localhost'"
**Solution:** Check your password in .env

```bash
# If MySQL has no password (common)
DB_PASSWORD=

# If MySQL has password
DB_PASSWORD=your_password_here

# Test connection
mysql -u root -p -e "SELECT 1;"
```

### Error: "Unknown database 'booking_system'"
**Solution:** Create the database

```bash
mysql -u root -e "CREATE DATABASE booking_system;"

# Verify
mysql -u root -e "SHOW DATABASES;" | grep booking_system
```

### Check Laravel Can Connect
```bash
cd booking-backend

# Test database connection
php artisan db

# Or run migrations to verify
php artisan migrate:status
```

## Backup & Restore

### Backup Database
```bash
# Backup entire database
mysqldump -u root booking_system > backup.sql

# Backup with password
mysqldump -u root -p booking_system > backup.sql
```

### Restore Database
```bash
# Restore from backup
mysql -u root booking_system < backup.sql

# Restore with password
mysql -u root -p booking_system < backup.sql
```

## Performance Optimization

### Add Indexes (Already in Migrations)

Indexes are automatically created on:
- `users.email`
- `users.payment_status`
- `bookings.user_id`
- `bookings.booking_date`
- `bookings.status`
- `personal_access_tokens.token`

### Connection Pooling (Optional)

For production, consider using:
- **MaxScale** - MySQL load balancer
- **ProxySQL** - MySQL proxy server

## Production Considerations

### Before Deploying

1. **Use Strong Password**
   ```env
   DB_PASSWORD=StrongSecurePassword123!
   ```

2. **Create Dedicated User**
   ```sql
   CREATE USER 'booking_app'@'localhost' IDENTIFIED BY 'SecurePassword123!';
   GRANT ALL PRIVILEGES ON booking_system.* TO 'booking_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Use Environment Variables**
   ```env
   DB_HOST=your-rds-endpoint.amazonaws.com
   DB_PORT=3306
   DB_DATABASE=booking_system
   DB_USERNAME=booking_app
   DB_PASSWORD=SecurePassword123!
   ```

4. **Enable Remote Access (if needed)**
   ```sql
   -- Allow connections from specific IP
   CREATE USER 'booking_app'@'192.168.1.100' IDENTIFIED BY 'SecurePassword123!';
   GRANT ALL PRIVILEGES ON booking_system.* TO 'booking_app'@'192.168.1.100';
   ```

5. **Regular Backups**
   - Automated daily backups
   - Test restore procedures
   - Keep backups in secure location

6. **Monitor Performance**
   ```bash
   # Check slow queries
   SHOW VARIABLES LIKE 'slow_query_log%';
   
   # Check current connections
   SHOW PROCESSLIST;
   ```

## Database Statistics

After running migrations, you'll have:
- **6 Tables** (users, bookings, cache, jobs, personal_access_tokens, job_batches)
- **12+ Indexes** for performance
- **Foreign Keys** for referential integrity
- **Automatic Timestamps** for audit trails

## Summary

Your backend is now configured for MySQL:
- ✅ Database: `booking_system`
- ✅ Host: `127.0.0.1`
- ✅ Port: `3306`
- ✅ 6 tables created automatically
- ✅ Proper indexes for performance
- ✅ Foreign key constraints
- ✅ Automatic timestamps

## Next Steps

1. **Ensure MySQL is running** - `brew services start mysql` (macOS)
2. **Create database** - `mysql -u root -e "CREATE DATABASE booking_system;"`
3. **Run migrations** - `php artisan migrate`
4. **Start backend** - `php artisan serve`
5. **Test connection** - Use curl examples in API_DOCUMENTATION.md

Your MySQL setup is complete! 🎉
