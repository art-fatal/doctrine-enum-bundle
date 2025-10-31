<?php

namespace ArtFatal\DoctrineEnumBundle;

use ArtFatal\DoctrineEnumBundle\DependencyInjection\Compiler\RegisterEnumTypesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Doctrine Enum Bundle
 *
 * This bundle provides automatic registration of PHP BackedEnum types
 * as Doctrine DBAL custom types, allowing seamless use of native PHP enums
 * with MySQL ENUM columns.
 */
class DoctrineEnumBundle extends Bundle
{
    /**
     * Adds the RegisterEnumTypesPass compiler pass to register all enum types.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterEnumTypesPass());
    }
}