<?php
declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Command;

use Oliverde8\Component\PhpEtl\Output\MermaidStaticOutput;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class GetDefinitionGraphCommand extends Command
{
    const OPTION_GETURL = 'get-url';

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
        $this->setName("etl:definition:graph");
        $this->addArgument("name", InputArgument::REQUIRED);
        $this->addOption(self::OPTION_GETURL, 'u', InputOption::VALUE_NONE);
    }

    /**
     * @throws \Oliverde8\Component\PhpEtl\Exception\ChainOperationException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sfs = new SymfonyStyle($input, $output);

        $chainName = $input->getArgument("name");
        $processor = $this->chainProcessorsManager->getProcessor($chainName, []);

        $mermaidOutput = new MermaidStaticOutput();

        if ($input->getOption(self::OPTION_GETURL)) {
            $graph = $mermaidOutput->generateUrl($processor);
            $sfs->title("Mermaid Graph Url");
            $output->writeln($graph);
        } else {
            $graph = $mermaidOutput->generateGrapText($processor);
            $sfs->title("Mermaid graph");
            $output->writeln($graph);
        }

        return 0;
    }
}
