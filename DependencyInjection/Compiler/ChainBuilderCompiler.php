<?php


namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;


use Oliverde8\Component\PhpEtl\ChainBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ChainBuilderCompiler implements CompilerPassInterface
{
    public const TAG = "etl.operation-factory";

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        $chainBuilderDefinition = $container->getDefinition(ChainBuilder::class);
        $factories = $container->findTaggedServiceIds(self::TAG);

        foreach ($factories as $id => $config) {
            $chainBuilderDefinition->addMethodCall("registerFactory", [new Reference($id)]);
        }
    }
}
