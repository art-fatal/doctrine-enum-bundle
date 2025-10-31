<?php

namespace ArtFatal\DoctrineEnumBundle\DependencyInjection;

use ArtFatal\DoctrineEnumBundle\Type\EnumType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Symfony extension for the DoctrineEnumBundle.
 *
 * This extension automatically tags all EnumType classes so they can be
 * registered with Doctrine by the compiler pass.
 */
class DoctrineEnumExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Auto-tag all classes extending EnumType
        $container->registerForAutoconfiguration(EnumType::class)
            ->addTag('doctrine_enum_bundle.enum_type');
    }
}