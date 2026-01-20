<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\PhpEtlBundle\Factory\ChainFactory;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ChainCompiler implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        $chainsArray = $container->getParameter("oliverde8-php-etl_chain");
        $chainsString = $container->getParameter("oliverde8-php-etl_chain__string");

        $chainProcessorManager = $container->getDefinition(ChainProcessorsManager::class);

        $chainProcessorManager->setArgument('$definitions', $chainsArray);
        $chainProcessorManager->setArgument('$rawDefinitions', $chainsString);


    }
}
