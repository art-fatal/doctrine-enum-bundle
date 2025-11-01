<?php

namespace ArtFatal\DoctrineEnumBundle\Enum;

/**
 * Built-in enum representing days of the week.
 *
 * This enum is provided by the DoctrineEnumBundle and ready to use out of the box.
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
     * Get weekend days (Saturday and Sunday)
     *
     * @return array<self>
     */
    public static function weekendDays(): array
    {
        return [self::SATURDAY, self::SUNDAY];
    }

    /**
     * Get weekdays (Monday to Friday)
     *
     * @return array<self>
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
     * Check if this day is a weekend
     */
    public function isWeekend(): bool
    {
        return in_array($this, self::weekendDays(), true);
    }

    /**
     * Check if this day is a weekday
     */
    public function isWeekday(): bool
    {
        return in_array($this, self::weekDays(), true);
    }

    /**
     * Get human-readable label (capitalized)
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get the numeric ISO-8601 day number (1 = Monday, 7 = Sunday)
     */
    public function toIsoNumber(): int
    {
        return match ($this) {
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
            self::SUNDAY => 7,
        };
    }

    /**
     * Create from ISO-8601 day number (1 = Monday, 7 = Sunday)
     */
    public static function fromIsoNumber(int $number): ?self
    {
        return match ($number) {
            1 => self::MONDAY,
            2 => self::TUESDAY,
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,
            5 => self::FRIDAY,
            6 => self::SATURDAY,
            7 => self::SUNDAY,
            default => null,
        };
    }
}