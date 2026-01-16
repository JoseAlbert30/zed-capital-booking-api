# New Database Structure - Complete ✅

## Database Schema Overview

Your database has been completely restructured according to your specifications with support for joint buyers.

### Tables Created

1. **users** - User accounts
2. **properties** - Real estate projects
3. **units** - Individual property units
4. **unit_user** - Pivot table for joint ownership
5. **user_attachments** - Document uploads
6. **bookings** - Appointment bookings

---

## Table Structures

### 1. users
Stores all user information including buyers and admin.

**Columns:**
- `id` - Primary key
- `full_name` - User's complete name
- `email` - Unique email address
- `password` - Hashed password
- `mobile_number` - Contact number (+63-917-xxx-xxxx)
- `payment_status` - ENUM: 'pending', 'partial', 'fully_paid'
- `payment_date` - Date of payment completion
- `email_verified_at` - Email verification timestamp
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Has many `bookings`
- Belongs to many `units` (through unit_user pivot)
- Has many `attachments`

---

### 2. properties
Real estate projects/developments.

**Columns:**
- `id` - Primary key
- `project_name` - Name of the project (e.g., "Vantage at Viera East")
- `location` - Full address/location
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Has many `units`

**Sample Data:**
- Vantage at Viera East (Viera East, Cainta, Rizal)
- Sunset Residences (123 Marina Boulevard)

---

### 3. units
Individual property units within projects.

**Columns:**
- `id` - Primary key
- `property_id` - Foreign key to properties
- `unit` - Unit identifier (e.g., "Tower A - Unit 1205")
- `status` - ENUM: 'unclaimed', 'claimed'
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `property_id, status` (composite)
- `unit`

**Relationships:**
- Belongs to `property`
- Belongs to many `users` (through unit_user pivot)

**Sample Data:**
- 8 units across 2 properties
- 4 claimed units (with owners)
- 4 unclaimed units

---

### 4. unit_user (Pivot Table for Joint Ownership)
Links users to units, supporting multiple buyers per unit.

**Columns:**
- `id` - Primary key
- `unit_id` - Foreign key to units
- `user_id` - Foreign key to users
- `is_primary` - Boolean (true for primary buyer, false for co-buyers)
- `created_at`, `updated_at` - Timestamps

**Unique Constraint:**
- `unit_id, user_id` (prevents duplicate ownership records)

**Usage Examples:**

**Single Owner:**
```
Unit 1 (Tower A - Unit 1205)
├── James Taylor (primary: true)
```

**Joint Ownership (2 buyers):**
```
Unit 2 (Tower A - Unit 1407)
├── Rachel Anderson (primary: true)
└── Matthew Thomas (primary: false)
```

**Joint Ownership (3 buyers):**
```
Unit 3 (Tower B - Unit 802)
├── Jennifer Jackson (primary: true)
├── Ryan White (primary: false)
└── Megan Harris (primary: false)
```

---

### 5. user_attachments
Document storage for users (SOAs, receipts, handover documents, images).

**Columns:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `filename` - File name (e.g., "soa_12_2026.pdf")
- `type` - ENUM: 'soa', 'handover', 'receipt', 'image'
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `user_id, type` (composite)

**Relationships:**
- Belongs to `user`

**Sample Data:**
- 9 attachments created for paid users
- Types include SOA and receipt documents

---

### 6. bookings
Appointment bookings for property viewings/handovers.

**Columns:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `booked_date` - Date of appointment
- `booked_time` - Time slot (e.g., "09:00 AM", "2:00 PM")
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to `user`

**Sample Data:**
- 5 bookings for paid users
- Various time slots throughout next 30 days

---

## Model Relationships

### User Model
```php
// Get all units (including joint ownership)
$user->units; // Returns all units user owns

// Get only primary units
$user->primaryUnits; // Where is_primary = true

// Get only co-buyer units  
$user->coBuyerUnits; // Where is_primary = false

// Get bookings
$user->bookings;

// Get attachments
$user->attachments;
```

### Unit Model
```php
// Get all buyers for a unit
$unit->users; // All buyers with pivot data

// Get primary buyer only
$unit->primaryBuyer(); // Single user where is_primary = true

// Get co-buyers only
$unit->coBuyers; // All users where is_primary = false

// Get property
$unit->property;
```

### Property Model
```php
// Get all units
$property->units;

// Get claimed units
$property->claimedUnits;

// Get unclaimed units
$property->unclaimedUnits;
```

---

## Sample Queries

### Find all co-owners of a unit
```php
$unit = Unit::find(2);
$buyers = $unit->users()->get();
foreach ($buyers as $buyer) {
    echo $buyer->full_name . " - " . 
         ($buyer->pivot->is_primary ? "Primary" : "Co-buyer");
}
```

### Get all units owned by a user
```php
$user = User::find(12);
$units = $user->units()->with('property')->get();
foreach ($units as $unit) {
    echo $unit->property->project_name . " - " . $unit->unit;
}
```

### Add a co-buyer to a unit
```php
$unit = Unit::find(1);
$user = User::find(5);
$unit->users()->attach($user->id, ['is_primary' => false]);
```

### Find all users with fully paid status who own units
```php
$buyers = User::where('payment_status', 'fully_paid')
    ->whereHas('units')
    ->with('units.property')
    ->get();
```

---

## Test Data Summary

### Users Created
- **1 Admin:** admin@bookingsystem.com (password: admin123)
- **20 Regular Users:** password123
  - First 10: Pending payment status
  - Last 10: Fully paid status

### Properties Created
- Vantage at Viera East (5 units)
- Sunset Residences (3 units)

### Ownership Examples
- **Single ownership:** James Taylor owns Tower A - Unit 1205
- **2-person joint:** Rachel Anderson (primary) + Matthew Thomas (co-buyer)
- **3-person joint:** Jennifer Jackson (primary) + Ryan White + Megan Harris (co-buyers)

### Documents
- 9 user attachments (SOAs and receipts)
- Attached to paid users

### Bookings
- 5 bookings created for paid users
- Various time slots

---

## API Integration Notes

The controllers will need updates to work with the new schema. Key changes needed:

1. **AuthController** - Update to use `full_name` and `mobile_number`
2. **UserController** - Add endpoints for units and attachments
3. **BookingController** - Update field names to `booked_date` and `booked_time`
4. **New Controllers needed:**
   - PropertyController - Manage properties
   - UnitController - Manage units and ownership
   - AttachmentController - Upload/manage documents

---

## Migration Commands

### Fresh install with seed data
```bash
php artisan migrate:fresh --seed
```

### View current structure
```bash
php artisan db
SHOW TABLES;
DESCRIBE users;
DESCRIBE units;
DESCRIBE unit_user;
```

### Query examples
```sql
-- View all joint ownerships
SELECT u.unit, usr.full_name, uu.is_primary 
FROM unit_user uu 
JOIN units u ON uu.unit_id = u.id 
JOIN users usr ON uu.user_id = usr.id
ORDER BY u.id, uu.is_primary DESC;

-- Find users with attachments
SELECT u.full_name, ua.type, ua.filename 
FROM users u 
JOIN user_attachments ua ON u.id = ua.user_id;
```

---

## Next Steps

1. **Update Controllers** - Modify existing controllers for new field names
2. **Create New Controllers** - Property, Unit, Attachment controllers
3. **Update Frontend** - Adjust to use new field names (full_name, mobile_number, etc.)
4. **File Upload** - Implement attachment upload functionality
5. **Unit Management** - Create UI for assigning units to buyers
6. **Joint Buyer Management** - Add/remove co-buyers from units

---

**Your database structure is ready with full joint ownership support!** 🎉

All migrations successful, seed data loaded, and relationships working correctly.
