<?php

namespace Oliverde8\PhpEtlBundle\Command;

use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GetDefinitionCommand extends Command
{
    /** @var ChainProcessorsManager */
    protected $chainProcessorsManager;

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
        $this->setName("etl:get-definition");
        $this->addArgument("name", InputArgument::REQUIRED);
    }

    /**
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $chainName = $input->getArgument("name");
        $definition = $this->chainProcessorsManager->getDefinition($chainName);

        echo Yaml::dump($definition, 4);
        return 0;
    }
}
