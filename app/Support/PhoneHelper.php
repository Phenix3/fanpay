<?php

declare(strict_types=1);

namespace App\Support;

class PhoneHelper
{
    /**
     * Format a phone number to Cameroon international format (237XXXXXXXXX).
     * Accepts: 6XXXXXXXX, 2376XXXXXXXX, +2376XXXXXXXX, 002376XXXXXXXX
     */
    public static function formatCameroon(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove country code prefix (00237 or 237)
        if (str_starts_with($phone, '00237')) {
            $phone = substr($phone, 5);
        } elseif (str_starts_with($phone, '237')) {
            $phone = substr($phone, 3);
        }

        // Remove leading 0
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Now we should have a 9-digit number starting with 6-9
        if (strlen($phone) === 9 && preg_match('/^[6-9]/', $phone)) {
            return '237'.$phone;
        }

        // If it already has 237 prefix and is 12 digits, return as-is
        if (strlen($phone) === 12 && str_starts_with($phone, '237')) {
            return $phone;
        }

        // Return original if we can't format it
        return $phone;
    }
}
