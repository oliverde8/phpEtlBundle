<?php

namespace Oliverde8\PhpEtlBundle\Command;

use Oliverde8\Component\PhpEtl\Output\SymfonyConsoleOutput;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends Command
{
    const ARGUMENT_NAME = "name";
    const ARGUMENT_DATA = "data";
    const ARGUMENT_PARAMS = "params";
    const OPTION_PRETTY = "pretty";

    protected ChainProcessorsManager $chainProcessorsManager;

    /**
     * ExecuteCommand constructor.
     * @param ChainProcessorsManager $chainProcessorsManager
     */
    public function __construct(ChainProcessorsManager $chainProcessorsManager)
    {
        parent::__construct();
        $this->chainProcessorsManager = $chainProcessorsManager;
    }

    protected function configure()
    {
        $this->setName("etl:execute");
        $this->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED);
        $this->addArgument(self::ARGUMENT_DATA, InputArgument::OPTIONAL, "json with the input array");
        $this->addArgument(self::ARGUMENT_PARAMS, InputArgument::OPTIONAL, "json with all the additional parameters");
        $this->addOption(self::OPTION_PRETTY, "p", InputOption::VALUE_NONE, "Disables pretty output");
    }

    /**
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $chainName = $input->getArgument(self::ARGUMENT_NAME);
        $options = json_decode($input->getArgument(self::ARGUMENT_PARAMS) ?? '[]', true);
        $data = json_decode($input->getArgument(self::ARGUMENT_DATA) ?? '[]', true);

        $processorOutput = new SymfonyConsoleOutput($output, 0);
        $observation = function (array $operationStates, int $processedItems, int $returnedItems, bool $hasFinished = false) use ($processorOutput) {
            $processorOutput->output($operationStates, $hasFinished);
        };
        if ($input->getOption(self::OPTION_PRETTY)) {
            $observation = function (array $operationStates)  {};
        }

        $this->chainProcessorsManager->execute($chainName, $data, $options, $observation);
        return 0;
    }
}
