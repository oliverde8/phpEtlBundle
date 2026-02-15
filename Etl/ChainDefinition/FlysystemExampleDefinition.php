<?php

namespace Oliverde8\PhpEtlBundle\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\ExternalFileFinderConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Loader\CsvFileWriterConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Transformer\ExternalFileProcessorConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;

/**
 * Example ETL chain that uses Flysystem to find and process files from local storage.
 *
 * This demonstrates the FlysystemExternalFileFinderCompiler in action.
 * It will look for CSV files in the configured Flysystem storage directory,
 * process them, and output the results.
 */
class FlysystemExampleDefinition implements ChainDefinitionInterface
{
    public function getKey(): string
    {
        return 'etl:flysystem-example';
    }

    public function build(): ChainConfig
    {
        $chainConfig = new ChainConfig();

        $chainConfig
            // Find CSV files from the default.storage Flysystem storage
            // This uses the flavor 'flysystem.default.storage' which was automatically
            // created by FlysystemExternalFileFinderCompiler
            ->addLink(new ExternalFileFinderConfig(
                directory: '/incoming',  // Relative to %kernel.project_dir%/var/storage/default
                flavor: 'flysystem.default.storage'
            ))

            // Move files from 'incoming' to 'incoming/processing' directory
            // This marks the file as being processed
            ->addLink(new ExternalFileProcessorConfig())

            // Extract data from the CSV file
            ->addLink(new CsvExtractConfig())

            // Write the processed data to an output CSV
            ->addLink(new CsvFileWriterConfig('processed-files.csv'))

            // Move files from 'incoming/processing' to 'incoming/processed' directory
            // This marks the file as successfully processed
            ->addLink(new ExternalFileProcessorConfig());

        return $chainConfig;
    }
}
