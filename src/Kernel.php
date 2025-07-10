<?php

namespace App;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\{Compiler\CompilerPassInterface, ContainerBuilder};
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        $container
            ->getDefinition("doctrine.orm.configuration")
            ->addMethodCall("setIdentityGenerationPreferences", [[PostgreSQLPlatform::class => ClassMetadata::GENERATOR_TYPE_SEQUENCE]]);
    }
}