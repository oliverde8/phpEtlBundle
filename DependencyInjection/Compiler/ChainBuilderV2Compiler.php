<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\ExternalFileFinderOperation;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\OperationConfig\OperationConfigInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ChainBuilderV2Compiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if ChainBuilderV2 service exists
        if (!$container->hasDefinition(ChainBuilderV2::class)) {
            return;
        }

        $chainBuilderDefinition = $container->getDefinition(ChainBuilderV2::class);

        // Find all services that implement ConfigurableChainOperationInterface
        $factories = [];

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $class = $definition->getClass();

            // Skip if class is null or not resolvable
            if (!$class || !class_exists($class)) {
                continue;
            }

            // Check if the service implements ConfigurableChainOperationInterface
            if (!is_a($class, ConfigurableChainOperationInterface::class, true)) {
                continue;
            }

            // Skip ExternalFileFinderOperation - it's handled by FlysystemExternalFileFinderCompiler
            if (is_a($class, ExternalFileFinderOperation::class, true)) {
                continue;
            }

            // Find the config class from the constructor
            $configClasses = $this->findConfigClasses($class);

            if (empty($configClasses)) {
                continue;
            }

            // Resolve all dependencies for this operation at compile time
            $injections = $this->resolveInjections($class, $definition, $container);

            // Create a GenericChainFactory for each config class
            foreach ($configClasses as $configClass) {
                $factoryId = 'php_etl.factory.' . str_replace('\\', '_', $serviceId . '_' . $configClass);
                $factoryDefinition = new Definition(GenericChainFactory::class);

                // Set factory arguments: operationClassName, configClassName, flavor, injections
                $factoryDefinition->setArguments([
                    $class,           // operationClassName
                    $configClass,     // configClassName
                    'default',        // flavor
                    $injections,      // resolved injections
                ]);

                $container->setDefinition($factoryId, $factoryDefinition);
                $factories[] = new Reference($factoryId);
            }
        }

        // Set the factories as the second argument to ChainBuilderV2
        $chainBuilderDefinition->setArgument(1, $factories);
    }

    /**
     * Resolve all constructor dependencies for an operation at compile time.
     * Uses the service definition's autowired arguments and respects #[Autowire] attributes.
     */
    private function resolveInjections(string $operationClass, Definition $operationDefinition, ContainerBuilder $container): array
    {
        $injections = [];

        try {
            $reflection = new \ReflectionClass($operationClass);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return [];
            }

            $parameters = $constructor->getParameters();
            $configuredArguments = $operationDefinition->getArguments();

            foreach ($parameters as $index => $parameter) {
                $name = $parameter->getName();
                $type = $parameter->getType();

                // Skip config parameter (will be injected by GenericChainFactory)
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();

                    if (is_a($typeName, OperationConfigInterface::class, true)) {
                        continue;
                    }

                    // Skip ChainBuilderV2 (will be injected by GenericChainFactory)
                    if (is_a($typeName, ChainBuilderV2::class, true)) {
                        continue;
                    }

                    // Skip flavor parameter (will be injected by GenericChainFactory)
                    if ($typeName === 'string' && $name === 'flavor') {
                        continue;
                    }
                }

                // First, check if the service definition already has this argument configured
                // This respects any manually configured arguments or autowiring
                if (isset($configuredArguments[$index]) || isset($configuredArguments['$' . $name])) {
                    $argument = $configuredArguments['$' . $name] ?? $configuredArguments[$index];
                    $injections[$name] = $argument;
                    continue;
                }

                // Check for #[Autowire] attribute on the parameter
                $autowireAttr = $this->getAutowireAttribute($parameter);
                if ($autowireAttr !== null) {
                    $injections[$name] = $this->resolveAutowireAttribute($autowireAttr, $container);
                    continue;
                }

                // Default autowiring: try to resolve by type
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();

                    if ($container->has($typeName)) {
                        $injections[$name] = new Reference($typeName);
                        continue;
                    }
                }

                // Try to resolve by parameter name
                if ($container->has($name)) {
                    $injections[$name] = new Reference($name);
                    continue;
                }

                // If parameter has default value, skip it (will use default)
                if ($parameter->isDefaultValueAvailable()) {
                    continue;
                }

                // If parameter is nullable, skip it (will be null)
                if ($parameter->allowsNull()) {
                    continue;
                }
            }

            return $injections;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Get the Autowire attribute from a parameter if it exists
     */
    private function getAutowireAttribute(\ReflectionParameter $parameter): ?object
    {
        // Check for Autowire attribute dynamically
        $attributes = $parameter->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            if (str_ends_with($attributeName, 'Autowire')) {
                try {
                    return $attribute->newInstance();
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Resolve a service reference from an Autowire attribute
     */
    private function resolveAutowireAttribute(object $autowire, ContainerBuilder $container): Reference|string|null
    {
        // Handle Autowire attribute properties dynamically
        // The Autowire attribute can specify a service, expression, or value

        // Try to get the service property
        if (property_exists($autowire, 'service') && $autowire->service) {
            return new Reference($autowire->service);
        }

        // Try to get value property (for parameter references like '%some.parameter%')
        if (property_exists($autowire, 'value') && $autowire->value !== null) {
            return $autowire->value;
        }

        return null;
    }

    /**
     * Find all config classes for an operation.
     * If the operation accepts an interface, returns all concrete implementations.
     * If the operation accepts a concrete class, returns that class.
     */
    private function findConfigClasses(string $operationClass): array
    {
        try {
            $reflection = new \ReflectionClass($operationClass);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // Check if this parameter type implements OperationConfigInterface
                if (class_exists($typeName) && is_a($typeName, OperationConfigInterface::class, true)) {
                    // Concrete class
                    return [$typeName];
                }

                // Check if it's an interface that extends OperationConfigInterface
                if (interface_exists($typeName) && is_a($typeName, OperationConfigInterface::class, true)) {
                    // Find all concrete implementations
                    return $this->findConcreteImplementations($typeName);
                }
            }

            return [];
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Find all concrete classes that implement a given interface by scanning the filesystem.
     */
    private function findConcreteImplementations(string $interfaceName): array
    {
        $implementations = [];

        // Common paths where config classes are defined
        $searchPaths = [
            __DIR__ . '/../../../php-etl/src/Oliverde8/Component/PhpEtl/OperationConfig',
            __DIR__ . '/../../Etl/OperationConfig',
        ];

        foreach ($searchPaths as $searchPath) {
            if (!is_dir($searchPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($searchPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Extract namespace and class name from file
                $content = file_get_contents($file->getPathname());

                // Extract namespace
                if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
                    $namespace = $nsMatches[1];

                    // Extract class name
                    if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
                        $className = $namespace . '\\' . $classMatches[1];

                        // Check if class exists and implements the interface
                        if (class_exists($className) && is_a($className, $interfaceName, true)) {
                            $implementations[] = $className;
                        }
                    }
                }
            }
        }

        return $implementations;
    }

    private function findConfigClass(string $operationClass): ?string
    {
        try {
            $reflection = new \ReflectionClass($operationClass);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return null;
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // Check if this parameter type implements OperationConfigInterface
                if (class_exists($typeName) && is_a($typeName, OperationConfigInterface::class, true)) {
                    return $typeName;
                }
            }

            return null;
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
