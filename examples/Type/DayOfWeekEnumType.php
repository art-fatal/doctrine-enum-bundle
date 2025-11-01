<?php

/**
 * EXAMPLE FILE - Copy this to your project
 *
 * Suggested location in your Symfony project:
 * src/Type/Enum/DayOfWeekEnumType.php
 *
 * Then update the namespace to match your project:
 * namespace App\Type\Enum;
 */

namespace YourApp\Type\Enum;

use YourApp\Constant\Enum\DayOfWeek;
use ArtFatal\DoctrineEnumBundle\Type\EnumType;

/**
 * Doctrine DBAL custom type for DayOfWeek enum.
 *
 * This type handles conversion between the PHP DayOfWeek enum
 * and MySQL ENUM column with values ('monday', 'tuesday', ...).
 *
 * Usage in entity:
 * #[ORM\Column(type: DayOfWeekEnumType::NAME)]
 * or
 * #[ORM\Column(type: 'day_of_week')]
 */
class DayOfWeekEnumType extends EnumType
{
    public const NAME = 'day_of_week';

    /**
     * Returns the fully qualified class name of the DayOfWeek enum.
     *
     * @return string
     */
    public static function getEnumsClass(): string
    {
        return DayOfWeek::class;
    }
}