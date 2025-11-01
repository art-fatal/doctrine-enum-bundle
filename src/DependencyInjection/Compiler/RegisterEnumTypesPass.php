<?php

namespace ArtFatal\DoctrineEnumBundle\DependencyInjection\Compiler;

use ArtFatal\DoctrineEnumBundle\Type\EnumType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that automatically registers all EnumType classes with Doctrine.
 *
 * This pass finds all services tagged with 'doctrine_enum_bundle.enum_type'
 * and registers them with Doctrine DBAL as custom types.
 */
class RegisterEnumTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Get existing Doctrine types configuration
        $typesDefinition = [];
        if ($container->hasParameter('doctrine.dbal.connection_factory.types')) {
            $typesDefinition = $container->getParameter('doctrine.dbal.connection_factory.types');
        }

        // Find all tagged enum types
        $taggedEnums = $container->findTaggedServiceIds('doctrine_enum_bundle.enum_type');

        /** @var EnumType $enumType */
        foreach ($taggedEnums as $enumType => $definition) {
            // Register each enum type with Doctrine
            $typesDefinition[$enumType::getTypeName()] = ['class' => $enumType];
        }

        // Update the parameter with all enum types
        $container->setParameter('doctrine.dbal.connection_factory.types', $typesDefinition);
    }
}