<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Tests\DependencyInjection;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\Expression\ExpressionEvaluatorInterface;
use Oliverde8\Component\PhpEtl\GenericChainFactory;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CommandCsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Grouping\BatchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\IfConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\SwitchConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\LogConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ThrottleConfig;
use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\ChainBuilderV2Compiler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Guards that the operations shipped by php-etl are actually usable through
 * ChainBuilderV2 in the bundle: an operation that isn't registered as a service
 * gets no GenericChainFactory, and building a chain using its config then fails
 * at runtime with "no factory found".
 */
class ChainBuilderV2CompilerTest extends TestCase
{
    public function testPhpEtl21OperationsGetAFactory(): void
    {
        $factories = $this->compileFactories();

        foreach ([
            BatchConfig::class,
            IfConfig::class,
            SwitchConfig::class,
            ThrottleConfig::class,
            CommandCsvExtractConfig::class,
        ] as $configClass) {
            self::assertArrayHasKey($configClass, $factories, "No ChainBuilderV2 factory for $configClass");
        }
    }

    public function testExpressionEvaluatorIsSharedAcrossOperations(): void
    {
        $factories = $this->compileFactories();

        foreach ([IfConfig::class, SwitchConfig::class, LogConfig::class] as $configClass) {
            $injections = $factories[$configClass]->getArgument(3);

            self::assertArrayHasKey('expressionEvaluator', $injections, "$configClass gets no shared evaluator");
            self::assertEquals(new Reference(ExpressionEvaluatorInterface::class), $injections['expressionEvaluator']);
        }
    }

    /**
     * @return array<class-string, Definition> GenericChainFactory definitions, keyed by config class.
     */
    private function compileFactories(): array
    {
        $container = new ContainerBuilder();
        $container->setDefinition(ChainBuilderV2::class, new Definition(ChainBuilderV2::class));
        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/Resources/config')))
            ->load('service-operations-v2.yml');
        // Runs before bundle passes in a real kernel; gives FQCN-id services their class.
        (new ResolveClassPass())->process($container);
        (new ChainBuilderV2Compiler())->process($container);

        $factories = [];
        foreach ($container->getDefinition(ChainBuilderV2::class)->getArgument(1) as $reference) {
            $definition = $container->getDefinition((string) $reference);
            self::assertSame(GenericChainFactory::class, $definition->getClass());
            $factories[$definition->getArgument(1)] = $definition;
        }

        return $factories;
    }
}
