<?php

namespace ArtFatal\DoctrineEnumBundle\Type;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use LogicException;

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
}