<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;

use League\Flysystem\FilesystemOperator;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\Model\File\FlySystemFileSystem;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates GenericChainFactory instances for ExternalFileFinderOperation
 * for each configured Flysystem storage.
 *
 * Each factory uses a flavor prefixed with "flysystem." followed by the storage name.
 * This allows users to specify which Flysystem storage to use via the flavor parameter.
 */
class FlysystemExternalFileFinderCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if ChainBuilderV2 service exists
        if (!$container->hasDefinition(ChainBuilderV2::class)) {
            return;
        }

        $chainBuilderDefinition = $container->getDefinition(ChainBuilderV2::class);

        // Find all Flysystem storage services
        // Flysystem bundle registers services with tag 'flysystem.storage'
        // or as services named like 'flysystem.storage.{name}'
        $flysystemStorages = $this->findFlysystemStorages($container);

        if (empty($flysystemStorages)) {
            return;
        }

        $factories = [];

        foreach ($flysystemStorages as $storageName => $storageServiceId) {
            // Create FlySystemFileSystem wrapper for this storage
            $fileSystemId = 'php_etl.filesystem.flysystem.' . $storageName;
            $fileSystemDefinition = new Definition(FlySystemFileSystem::class);
            $fileSystemDefinition->setArguments([
                new Reference($storageServiceId)
            ]);
            $container->setDefinition($fileSystemId, $fileSystemDefinition);

            // Create GenericChainFactory for ExternalFileFinderOperation with this filesystem
            $factoryId = 'php_etl.factory.external_file_finder.flysystem.' . $storageName;
            $factoryDefinition = new Definition(GenericChainFactory::class);

            $flavor = 'flysystem.' . $storageName;

            $factoryDefinition->setArguments([
                ExternalFileFinderOperation::class,  // operationClassName
                ExternalFileFinderConfig::class,     // configClassName
                $flavor,                              // flavor
                ['fileSystem' => new Reference($fileSystemId)], // injections
            ]);

            $container->setDefinition($factoryId, $factoryDefinition);
            $factories[] = new Reference($factoryId);
        }

        // Add the factories to ChainBuilderV2's existing factories
        $existingFactories = $chainBuilderDefinition->getArgument(1);
        if (!is_array($existingFactories)) {
            $existingFactories = [];
        }

        $allFactories = array_merge($existingFactories, $factories);
        $chainBuilderDefinition->setArgument(1, $allFactories);
    }

    /**
     * Find all Flysystem storage services in the container.
     *
     * @return array<string, string> Map of storage name => service ID
     */
    private function findFlysystemStorages(ContainerBuilder $container): array
    {
        $storages = [];

        // Strategy 1: Find services with flysystem.storage tag
        $taggedServices = $container->findTaggedServiceIds('flysystem.storage');
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $storageName = $tag['storage'] ?? $this->extractStorageNameFromServiceId($serviceId);
                if ($storageName) {
                    $storages[$storageName] = $serviceId;
                }
            }
        }

        // Strategy 2: Find services matching pattern flysystem.storage.*
        // FlysystemBundle typically registers services like:
        // - flysystem.storage.default
        // - flysystem.storage.{name}
        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if (preg_match('/^flysystem\.storage\.(.+)$/', $serviceId, $matches)) {
                $storageName = $matches[1];
                if (!isset($storages[$storageName])) {
                    // Verify this service implements FilesystemOperator
                    $class = $definition->getClass();
                    if ($class && is_a($class, FilesystemOperator::class, true)) {
                        $storages[$storageName] = $serviceId;
                    }
                }
            }
        }

        // Strategy 3: Check for aliased services
        foreach ($container->getAliases() as $aliasId => $alias) {
            if (preg_match('/^flysystem\.storage\.(.+)$/', $aliasId, $matches)) {
                $storageName = $matches[1];
                if (!isset($storages[$storageName])) {
                    $storages[$storageName] = (string) $alias;
                }
            }
        }

        return $storages;
    }

    /**
     * Extract storage name from service ID.
     *
     * @param string $serviceId
     * @return string|null
     */
    private function extractStorageNameFromServiceId(string $serviceId): ?string
    {
        if (preg_match('/flysystem\.storage\.(.+)$/', $serviceId, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

