<?php


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
    }
}
