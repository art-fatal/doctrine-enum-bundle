<?php

namespace ArtFatal\DoctrineEnumBundle\Type;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use LogicException;
use ReflectionClass;

/**
 * Abstract base class for Doctrine ENUM types.
 *
 * This class provides automatic conversion between PHP BackedEnum and MySQL ENUM columns.
 * Extend this class and implement getEnumsClass() to create your own enum types.
 */
abstract class EnumType extends Type
{
    /**
     * Generates the SQL declaration for the ENUM column.
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        /** @var BackedEnum $enum */
        $enum = $this::getEnumsClass();
        $values = array_map(function($enum) {
            return "'".$enum->value."'";
        }, $enum::cases());

        return "ENUM(".implode(", ", $values).")";
    }

    /**
     * Converts a PHP BackedEnum to a database value (string).
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        return null;
    }

    /**
     * Converts a database value (string) to a PHP BackedEnum.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (false === enum_exists($this::getEnumsClass())) {
            throw new LogicException("Class {$this::getEnumsClass()} should be an enum");
        }

        return $this::getEnumsClass()::tryFrom($value);
    }

    /**
     * Returns the fully qualified class name of the PHP BackedEnum.
     *
     * @return string The FQCN of the enum class
     */
    abstract public static function getEnumsClass(): string;

    /**
     * Automatically generates the type name from the class name in snake_case.
     * This is a static method that can be used in attributes.
     *
     * Example: DayOfWeekEnumType::getTypeName() -> 'day_of_week'
     *
     * Usage in entity:
     * #[ORM\Column(type: DayOfWeekEnumType::getTypeName())]
     *
     * @return string The type name in snake_case
     */
    public static function getTypeName(): string
    {
        // Get the short class name (without namespace) using late static binding
        $className = (new ReflectionClass(static::class))->getShortName();

        // Remove "EnumType" or "Type" suffix if present
        $className = preg_replace('/EnumType$/', '', $className);
        $className = preg_replace('/Type$/', '', $className);

        // Convert to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Returns the type name for Doctrine registration.
     * Uses the static getTypeName() method.
     *
     * @return string The type name in snake_case
     */
    public function getName(): string
    {
        return static::getTypeName();
    }
}