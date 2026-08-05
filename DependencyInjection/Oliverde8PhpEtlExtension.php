<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Oliverde8PhpEtlExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('service-rule-transformers.yml');
        $loader->load('service-operation-factories.yml');
        $loader->load('service-operations-v2.yml');

        // ChainBuilderV2 operations that only exist on newer php-etl versions
        // (Grouping/BatchOperation, IfOperation, SwitchOperation — all added in
        // 2.1). The whole file loads or none of it does, so every class it
        // references must be checked here — a service definition pointing at a
        // missing class fails container compilation, unlike a plain instanceof
        // check. This keeps the bundle working against older versions that
        // don't have them yet.
        if (class_exists(\Oliverde8\Component\PhpEtl\ChainOperation\Grouping\BatchOperation::class)
            && class_exists(\Oliverde8\Component\PhpEtl\ChainOperation\IfOperation::class)
            && class_exists(\Oliverde8\Component\PhpEtl\ChainOperation\SwitchOperation::class)
        ) {
            $loader->load('service-operations-v2-optional.yml');
        }

        // Optional real-time layer: only wire the Mercure publisher when the
        // component is actually installed. Without it the bundle keeps the
        // no-op publisher and the graph degrades to polling / static.
        if (interface_exists(\Symfony\Component\Mercure\HubInterface::class)) {
            $loader->load('services-mercure.yml');
        }
    }
}
