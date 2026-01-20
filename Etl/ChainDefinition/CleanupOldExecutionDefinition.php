<?php

declare(strict_types = 1);

namespace Oliverde8\PhpEtlBundle\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteEntityForOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteFilesForOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\FindOldExecutionConfig;

class CleanupOldExecutionDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'etl:cleanup_old_execution';
    }

    public function build(): ChainConfig
    {
        return new ChainConfig()
            ->addLink(new FindOldExecutionConfig((new \DateTime())->modify('-1 month')))
            ->addLink(new DeleteEntityForOldExecutionConfig())
            ->addLink(new DeleteFilesForOldExecutionConfig());
    }
}
