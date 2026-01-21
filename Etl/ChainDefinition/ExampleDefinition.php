<?php

namespace Oliverde8\PhpEtlBundle\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SimpleHttpConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\SplitItemConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteEntityForOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteFilesForOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\FindOldExecutionConfig;

class
ExampleDefinition implements ChainDefinitionInterface
{

    public function getKey(): string
    {
        return 'etl:example';
    }

    public function build(): ChainConfig
    {
        $chainConfig = new ChainConfig();
        $chainConfig->addLink(new SimpleHttpConfig(
                method: 'GET',
                url: 'https://63b687951907f863aaf90ab1.mockapi.io/test',
                responseIsJson: true
            ))
            ->addLink(new SplitItemConfig(
                keys: ['content'],
                singleElement: true
            ))
            ->addLink(new CsvFileWriterConfig('output.csv'));
    }
}
