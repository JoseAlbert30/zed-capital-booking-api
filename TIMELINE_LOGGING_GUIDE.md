# Timeline Logging System

## Overview
The booking system now features a comprehensive timeline logging system that automatically tracks all user-related actions and allows manual note additions. All events are stored in a dedicated `remarks` table with a foreign key relationship to users.

## Database Schema

### Remarks Table
The `remarks` table stores all timeline entries:

```sql
CREATE TABLE remarks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  event TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'system',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_type (type)
);
```

### Remark Model
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remark extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'time',
        'event',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### User Relationship
```php
public function remarks(): HasMany
{
    return $this->hasMany(Remark::class)
        ->orderBy('date', 'desc')
        ->orderBy('time', 'desc');
}
```

## Timeline Structure

Each timeline entry has the following fields:

- **id**: Auto-incrementing primary key
- **user_id**: Foreign key to users table
- **date**: Date when event occurred (Y-m-d format)
- **time**: Time when event occurred (H:i:s format)
- **event**: Description of the event
- **type**: Event type classification
- **created_at/updated_at**: Laravel timestamps

## Automatic Event Types

### 1. Email Events (`email_sent`)

**Initial Email Send:**
- Event: "Initialization Email sent (SOA, Handover docs, etc)"
- Type: `email_sent`

**Resent Email:**
- Event: "Resent Initialization Email (SOA, Handover docs, etc)"
- Type: `email_sent`

The system automatically detects if an initialization email was already sent by querying the remarks table.

**Location:** `EmailController::sendSOAEmail()`

### 2. Payment Updates (`payment_update`)

**Manual Payment Status Change:**
- Event: "Payment status updated from PENDING to FULLY PAID"
- Type: `payment_update`

**Automatic Co-owner Update:**
- Event: "Payment status automatically updated to FULLY PAID (co-owner payment completed)"
- Type: `payment_update`

When one co-owner's payment is marked as fully paid, all other co-owners are automatically updated and receive this timeline entry.

**Location:** `UserController::updatePaymentStatus()`

### 3. Manual Notes (`manual_note`)

Admins can add custom notes through the user detail page interface.

- Type: `manual_note`
- Event: Custom text entered by admin

**Location:** `UserController::addRemark()`

## Implementation Details

### Backend Helper Method

A reusable helper method is available in `UserController`:

```php
private function addRemarkToUser(User $user, string $event, string $type = 'system'): void
{
    $currentRemarks = $user->remarks ?? [];
    $currentRemarks[] = [
        'date' => now()->format('Y-m-d'),
        'time' => now()->format('H:i:s'),
        'event' => $event,
        'type' => $type
    ];
    $user->update(['remarks' => $currentRemarks]);
}
```

### Usage Examples

**In EmailController:**
```php
// Check if already sent
$alreadySent = Remark::where('user_id', $user->id)
    ->where('type', 'email_sent')
    ->exists();

$eventText = $alreadySent 
    ? 'Resent Initialization Email (SOA, Handover docs, etc)'
    : 'Initialization Email sent (SOA, Handover docs, etc)';

// Create remark entry
Remark::create([
    'user_id' => $user->id,
    'date' => now()->format('Y-m-d'),
    'time' => now()->format('H:i:s'),
    'event' => $eventText,
    'type' => 'email_sent'
]);
```

**In UserController:**
```php
// Using helper method
$this->addRemarkToUser($user, 
    'Payment status updated from ' . strtoupper($oldStatus) . ' to ' . strtoupper($request->payment_status),
    'payment_update'
);
```

## API Endpoints

### Add Manual Remark
```
POST /api/users/{user}/remarks
Authorization: Bearer {token}

Request Body:
{
  "remark": "Customer confirmed handover appointment"
}

Response:
{
  "message": "Remark added successfully",
  "remarks": [
    {
      "date": "2026-01-12",
      "time": "14:30:45",
      "event": "Customer confirmed handover appointment",
      "type": "manual_note"
    }
  ]
}
```

## Frontend Implementation

### Display Timeline

The user detail page displays remarks as a visual timeline:

```tsx
<div className="relative border-l-2 border-gray-300 pl-6 space-y-4">
  {remarks.map((entry, index) => (
    <div key={index} className="relative">
      {/* Timeline dot */}
      <div className="absolute -left-[26px] w-3 h-3 rounded-full bg-blue-600 border-2 border-white"></div>
      
      {/* Timeline content */}
      <div className="bg-gray-50 rounded-lg p-4 shadow-sm">
        <div className="flex items-start justify-between mb-2">
          <div className="flex items-center gap-2">
            <Calendar className="w-4 h-4 text-gray-500" />
            <span className="text-sm font-medium text-gray-700">
              {entry.date} at {entry.time}
            </span>
          </div>
          {entry.type && (
            <Badge variant={entry.type === 'email_sent' ? 'default' : 'secondary'}>
              {entry.type.replace('_', ' ').toUpperCase()}
            </Badge>
          )}
        </div>
        <p className="text-gray-800">{entry.event}</p>
      </div>
    </div>
  ))}
</div>
```

### Add Manual Remark

```tsx
const handleAddRemark = async () => {
  const token = localStorage.getItem("authToken");
  const result = await addUserRemark(parseInt(userId), newRemark, token);
  setRemarks(result.remarks);
  setNewRemark("");
};
```

## Future Event Types

You can easily add more automatic logging for:

### Booking Confirmed (`booking_confirmed`)
```php
$this->addRemarkToUser($user, 
    'Booking confirmed for Unit ' . $unit->unit . ' at ' . $project->project_name,
    'booking_confirmed'
);
```

### Handover Confirmed (`handover_confirmed`)
```php
$this->addRemarkToUser($user, 
    'Handover completed for Unit ' . $unit->unit,
    'handover_confirmed'
);
```

### Document Uploaded (`document_uploaded`)
```php
$this->addRemarkToUser($user, 
    'Receipt uploaded for ' . strtoupper($paymentStatus) . ' payment',
    'document_uploaded'
);
```

### Password Reset (`password_reset`)
```php
$this->addRemarkToUser($user, 
    'Password regenerated by admin',
    'password_reset'
);
```

## Database Schema

The `remarks` table structure:

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| user_id | BIGINT UNSIGNED | Foreign key to users table |
| date | DATE | Event date (Y-m-d) |
| time | TIME | Event time (H:i:s) |
| event | TEXT | Event description |
| type | VARCHAR(50) | Event type classification |
| created_at | TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | Record update timestamp |

### Indexes
- Primary key on `id`
- Foreign key on `user_id` (CASCADE on delete)
- Index on `user_id` for faster queries
- Index on `type` for filtering by event type

### Migration
```php
Schema::create('remarks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->time('time');
    $table->text('event');
    $table->string('type', 50)->default('system');
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('type');
});
```

### User Model Relationship
```php
public function remarks(): HasMany
{
    return $this->hasMany(Remark::class)
        ->orderBy('date', 'desc')
        ->orderBy('time', 'desc');
}
```

### Eager Loading
When fetching users, remarks are automatically eager loaded:
```php
$user = User::with(['remarks'])->find($id);
// Access remarks
$userRemarks = $user->remarks; // Collection of Remark models
```

## Best Practices

1. **Always include type:** Helps with filtering and display styling
2. **Be descriptive:** Event descriptions should be clear and informative
3. **Use past tense:** "Email sent" not "Email sending"
4. **Include context:** Specify unit numbers, amounts, dates when relevant
5. **Consistent formatting:** Use the helper method when possible
6. **Automatic detection:** Check for existing events when needed (like resent emails)

## Timeline Event Colors (Frontend)

Different event types can be styled differently:

- `email_sent` → Blue badge, default variant
- `payment_update` → Green badge for completed, yellow for partial
- `manual_note` → Gray badge, secondary variant
- `booking_confirmed` → Green badge
- `handover_confirmed` → Purple badge
- `document_uploaded` → Orange badge

Update the Badge variant logic in the frontend to add more event-specific styling.
