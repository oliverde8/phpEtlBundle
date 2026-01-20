<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Factory;

use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\PhpEtlBundle\Services\ExecutionContextFactory;

class ChainFactory
{
    public function __construct(
        protected readonly ChainBuilder $chainBuilder,
        protected readonly ChainBuilderV2 $chainBuilderV2,
    ){}

    public function create($config, array $inputOptions, int $maxAsynchronousItems): ChainProcessor
    {
        return $this->chainBuilder->buildChainProcessor($config, $inputOptions, $maxAsynchronousItems);
    }

    public function createFromDefinition(ChainDefinitionInterface $definition): ChainProcessor
    {
        return $this->chainBuilderV2->createChain($definition->build());
    }
}
