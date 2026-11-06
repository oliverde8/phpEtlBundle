<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Tests\DependencyInjection;

use Oliverde8\Component\PhpEtl\Builder\Factories\ChainRepeatFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\ChainSplitFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\LogFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainRepeatOperationV1;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperationV1;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\ChainBuilderCompiler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ChainBuilderCompilerTest extends TestCase
{
    public function testYamlRepeatUsesLegacyOperation(): void
    {
        $chain = $this->createBuilder()->buildChainProcessor([
            'repeat' => [
                'operation' => 'repeat',
                'options' => [
                    'maxASynchronousItems' => 0,
                    'validationExpr' => 'false',
                    'chain' => [
                        'log' => [
                            'operation' => 'log',
                            'options' => ['message' => 'Repeated', 'level' => 'info', 'context' => []],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertInstanceOf(ChainRepeatOperationV1::class, $chain->getChainLinks()[0]);
    }

    public function testYamlSplitUsesLegacyOperation(): void
    {
        $chain = $this->createBuilder()->buildChainProcessor([
            'split' => [
                'operation' => 'split',
                'options' => ['branches' => [[]]],
            ],
        ]);

        self::assertInstanceOf(ChainSplitOperationV1::class, $chain->getChainLinks()[0]);
    }

    private function createBuilder(): ChainBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/Resources/config')))
            ->load('service-operation-factories.yml');

        foreach (array_keys($container->getDefinitions()) as $id) {
            if ($container->getDefinition($id)->hasTag('etl.operation-factory')
                && !in_array($id, [ChainRepeatFactory::class, ChainSplitFactory::class, LogFactory::class], true)) {
                $container->removeDefinition($id);
            }
        }

        $container->register(ExecutionContextFactoryInterface::class, ExecutionContextFactory::class);
        $container->register(ChainBuilder::class, ChainBuilder::class)->setAutowired(true)->setPublic(true);
        $container->addCompilerPass(new ChainBuilderCompiler());
        $container->compile();

        return $container->get(ChainBuilder::class);
    }
}
