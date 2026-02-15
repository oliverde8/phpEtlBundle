<?php

namespace Oliverde8\PhpEtlBundle\Command;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\Extract\CsvExtractConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'etl:debug:factories',
    description: 'Debug ChainBuilderV2 factories to verify they are registered correctly'
)]
class DebugFactoriesCommand extends Command
{
    public function __construct(
        private readonly ChainBuilderV2 $chainBuilder
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('ChainBuilderV2 Factory Debug');

        // Test if CsvExtractConfig factory is registered
        $io->section('Testing CsvExtractConfig Factory');

        try {
            $config = new ChainConfig();
            $config->addLink(new CsvExtractConfig());

            $chain = $this->chainBuilder->createChain($config);

            $io->success('✓ CsvExtractConfig factory is registered and working!');
            $io->info('Chain created successfully with CsvExtractConfig');
        } catch (\Exception $e) {
            $io->error('✗ CsvExtractConfig factory is NOT working');
            $io->error('Error: ' . $e->getMessage());
            $io->note('Solution: Run "bin/console cache:clear" to rebuild the container');
            return Command::FAILURE;
        }

        // Get factory information using reflection
        $io->section('ChainBuilder Factory Information');

        try {
            $reflection = new \ReflectionClass($this->chainBuilder);
            $factoriesProperty = $reflection->getProperty('factories');
            $factoriesProperty->setAccessible(true);
            $factories = $factoriesProperty->getValue($this->chainBuilder);

            $io->info(sprintf('Total factories registered: %d', count($factories)));

            $io->section('Registered Factories');
            $factoryInfo = [];

            foreach ($factories as $index => $factory) {
                $factoryReflection = new \ReflectionClass($factory);
                $opClassProp = $factoryReflection->getProperty('operationClassName');
                $opClassProp->setAccessible(true);
                $configClassProp = $factoryReflection->getProperty('configClassName');
                $configClassProp->setAccessible(true);
                $flavorProp = $factoryReflection->getProperty('flavor');
                $flavorProp->setAccessible(true);

                $operationClass = $opClassProp->getValue($factory);
                $configClass = $configClassProp->getValue($factory);
                $flavor = $flavorProp->getValue($factory);

                // Extract short class names for readability
                $operationShort = substr($operationClass, strrpos($operationClass, '\\') + 1);
                $configShort = substr($configClass, strrpos($configClass, '\\') + 1);

                $factoryInfo[] = [
                    $index + 1,
                    $operationShort,
                    $configShort,
                    $flavor
                ];
            }

            $io->table(
                ['#', 'Operation', 'Config', 'Flavor'],
                $factoryInfo
            );

            // Highlight if CsvExtractConfig is found
            $csvExtractFound = false;
            foreach ($factories as $factory) {
                $factoryReflection = new \ReflectionClass($factory);
                $configClassProp = $factoryReflection->getProperty('configClassName');
                $configClassProp->setAccessible(true);
                $configClass = $configClassProp->getValue($factory);

                if ($configClass === CsvExtractConfig::class) {
                    $csvExtractFound = true;
                    break;
                }
            }

            if ($csvExtractFound) {
                $io->success('CsvExtractConfig factory is in the list!');
            } else {
                $io->warning('CsvExtractConfig factory NOT found in the list!');
                $io->note('Run "bin/console cache:clear" to rebuild the container');
            }

        } catch (\Exception $e) {
            $io->warning('Could not inspect factories using reflection');
            $io->text('Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}

