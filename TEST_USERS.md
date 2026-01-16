# Test User Credentials

## Admin User

**Email:** `admin@bookingsystem.com`  
**Password:** `admin123`  
**Status:** fully_paid

Admin user has access to:
- Admin dashboard in Next.js frontend
- User management endpoints
- Booking management endpoints
- All admin operations

---

## Regular Test Users (20 accounts)

All regular users have the password: `password123`

### Unpaid Users (Payment Status: pending)
These users cannot book until they update their payment status to `fully_paid`.

| # | Name | Email | Payment Status |
|---|------|-------|-----------------|
| 1 | John Smith | john.smith@example.com | pending |
| 2 | Sarah Johnson | sarah.johnson@example.com | pending |
| 3 | Michael Brown | michael.brown@example.com | pending |
| 4 | Emily Davis | emily.davis@example.com | pending |
| 5 | David Wilson | david.wilson@example.com | pending |
| 6 | Jessica Martinez | jessica.martinez@example.com | pending |
| 7 | Christopher Garcia | chris.garcia@example.com | pending |
| 8 | Laura Rodriguez | laura.rodriguez@example.com | pending |
| 9 | Daniel Lee | daniel.lee@example.com | pending |
| 10 | Amanda White | amanda.white@example.com | pending |

### Paid Users (Payment Status: fully_paid)
These users can immediately book appointments.

| # | Name | Email | Payment Status |
|---|------|-------|-----------------|
| 11 | James Taylor | james.taylor@example.com | fully_paid |
| 12 | Rachel Anderson | rachel.anderson@example.com | fully_paid |
| 13 | Matthew Thomas | matthew.thomas@example.com | fully_paid |
| 14 | Jennifer Jackson | jennifer.jackson@example.com | fully_paid |
| 15 | Ryan White | ryan.white@example.com | fully_paid |
| 16 | Megan Harris | megan.harris@example.com | fully_paid |
| 17 | Brandon Martin | brandon.martin@example.com | fully_paid |
| 18 | Stephanie Clark | stephanie.clark@example.com | fully_paid |
| 19 | Kevin Lewis | kevin.lewis@example.com | fully_paid |
| 20 | Nicole Walker | nicole.walker@example.com | fully_paid |

---

## Testing Workflow

### 1. Admin Dashboard Testing
```
Login Email: admin@bookingsystem.com
Login Password: admin123
```

Then access:
- View all users
- View all bookings
- Manage user payment statuses
- Check system health

### 2. Regular User Testing

**Test Paid User:**
```
Email: james.taylor@example.com
Password: password123
Status: Can create bookings immediately
```

**Test Unpaid User:**
```
Email: john.smith@example.com
Password: password123
Status: Cannot create bookings (needs payment)
```

### 3. Update Payment Status (Admin only)

Use the API endpoint to update a user's payment status:

```bash
curl -X POST http://localhost:8000/api/admin/users/{userId}/payment-status \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"payment_status": "fully_paid"}'
```

This allows an unpaid user to become eligible for booking.

---

## Key Features to Test

### With Paid Users (can book)
- ✅ User registration
- ✅ User login
- ✅ View available booking slots
- ✅ Create bookings
- ✅ View their bookings
- ✅ Cancel bookings

### With Unpaid Users (cannot book)
- ✅ User registration
- ✅ User login
- ✅ View profile
- ❌ Cannot view available slots (error)
- ❌ Cannot create bookings (error)
- ✅ View pending bookings (if any)

### Admin Only
- ✅ View all users
- ✅ View all bookings
- ✅ Update user payment status
- ✅ Regenerate user passwords
- ✅ Delete bookings
- ✅ Cancel bookings

---

## Database Quick Reference

### Check User Count
```bash
mysql -u root booking_system -e "SELECT COUNT(*) as total_users FROM users;"
```

### Check Payment Status Distribution
```bash
mysql -u root booking_system -e "SELECT payment_status, COUNT(*) FROM users GROUP BY payment_status;"
```

### View All Users
```bash
mysql -u root booking_system -e "SELECT id, name, email, payment_status FROM users ORDER BY id;"
```

### Reset Users (Start Fresh)
```bash
cd booking-backend
php artisan migrate:fresh --seed
```

---

## Next Steps

1. **Start Backend Server**
   ```bash
   cd booking-backend
   php artisan serve
   ```

2. **Test API Endpoints** (see API_DOCUMENTATION.md for full list)
   ```bash
   # Example: Login as admin
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{
       "email": "admin@bookingsystem.com",
       "password": "admin123"
     }'
   ```

3. **Connect Next.js Frontend**
   - Update API base URL in frontend
   - Use admin token for admin dashboard
   - Use regular user tokens for customer booking

4. **Run Test Cases**
   - Test registration
   - Test login (admin + regular users)
   - Test booking (paid vs unpaid users)
   - Test admin functions

---

**All test users are ready to use!** 🚀

Feel free to create additional test accounts as needed using the Laravel API or directly in the database.
