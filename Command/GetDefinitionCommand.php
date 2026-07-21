<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Command;

use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GetDefinitionCommand extends Command
{
    /**
     * ExecuteCommand constructor.
     * @param ChainProcessorsManager $chainProcessorsManager
     */
    public function __construct(protected readonly ChainProcessorsManager $chainProcessorsManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName("etl:get-definition");
        $this->addArgument("name", InputArgument::REQUIRED);
    }

    /**
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chainName = $input->getArgument("name");
        $definition = $this->chainProcessorsManager->getRawDefinition($chainName);

        if ($definition instanceof ChainDefinitionInterface) {
            $output->writeln(sprintf(
                'V2 (PHP) chain definition: %s (key: %s)',
                $definition::class,
                $definition->getKey()
            ));

            return Command::SUCCESS;
        }

        // V1 (YAML) chains are stored as their raw YAML string; re-dump for consistent formatting.
        $output->writeln(Yaml::dump(Yaml::parse($definition), 4));

        return Command::SUCCESS;
    }
}
