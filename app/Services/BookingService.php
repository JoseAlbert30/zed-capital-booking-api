<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class BookingService
{
    const TIME_SLOTS = [
        '09:00', '10:00', '11:00', '12:00', '13:00', '14:00',
        '15:00', '16:00', '17:00'
    ];

    public function checkBookingEligibility(User $user): array
    {
        if ($user->payment_status === 'pending') {
            return [
                'eligible' => false,
                'reason' => 'Payment pending. Please complete your payment to schedule a handover.',
            ];
        }

        if ($user->payment_status === 'partial') {
            return [
                'eligible' => false,
                'reason' => 'Partial payment received. Please complete full payment to schedule a handover.',
            ];
        }

        // Fully paid users can book immediately (frontend handles date restrictions)
        return [
            'eligible' => true,
            'reason' => 'User is eligible to book',
        ];
    }

    public function getAvailableSlots(string $date): array
    {
        $bookingDate = Carbon::parse($date)->startOfDay();

        $bookedSlots = Booking::where('booked_date', '>=', $bookingDate)
            ->where('booked_date', '<', $bookingDate->copy()->addDay())
            ->pluck('booked_time')
            ->toArray();

        $availableSlots = array_diff(self::TIME_SLOTS, $bookedSlots);

        return array_values($availableSlots);
    }

    public static function getAllTimeSlots(): array
    {
        return self::TIME_SLOTS;
    }
}
