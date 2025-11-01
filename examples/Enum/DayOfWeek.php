<?php

/**
 * EXAMPLE FILE - Copy this to your project
 *
 * Suggested location in your Symfony project:
 * src/Constant/Enum/DayOfWeek.php
 *
 * Then update the namespace to match your project:
 * namespace App\Constant\Enum;
 */

namespace YourApp\Constant\Enum;

/**
 * Example enum representing days of the week.
 *
 * This is a simple BackedEnum with string values.
 * Each case represents a day of the week in lowercase.
 */
enum DayOfWeek: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    /**
     * Constant containing all enum cases.
     * Useful for validation constraints like Assert\Choice.
     */
    public const ALL = [
        self::MONDAY,
        self::TUESDAY,
        self::WEDNESDAY,
        self::THURSDAY,
        self::FRIDAY,
        self::SATURDAY,
        self::SUNDAY,
    ];

    /**
     * Example helper method: Get weekend days
     */
    public static function weekendDays(): array
    {
        return [self::SATURDAY, self::SUNDAY];
    }

    /**
     * Example helper method: Get weekdays
     */
    public static function weekDays(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
        ];
    }

    /**
     * Example helper method: Check if this day is a weekend
     */
    public function isWeekend(): bool
    {
        return in_array($this, self::weekendDays(), true);
    }

    /**
     * Example helper method: Get human-readable label
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}