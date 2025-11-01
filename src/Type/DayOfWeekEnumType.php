<?php

namespace ArtFatal\DoctrineEnumBundle\Type;

use ArtFatal\DoctrineEnumBundle\Enum\DayOfWeek;

/**
 * Built-in Doctrine DBAL custom type for DayOfWeek enum.
 *
 * This type is provided by the DoctrineEnumBundle and ready to use out of the box.
 * It handles conversion between the PHP DayOfWeek enum and MySQL ENUM column
 * with values ('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday').
 *
 * Usage in entity:
 * ```php
 * use ArtFatal\DoctrineEnumBundle\Enum\DayOfWeek;
 * use ArtFatal\DoctrineEnumBundle\Type\DayOfWeekEnumType;
 *
 * #[ORM\Column(type: DayOfWeekEnumType::NAME)]
 * private ?DayOfWeek $dayOfWeek = null;
 * ```
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