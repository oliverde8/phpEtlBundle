<?php

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Item\StopItem;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\FindOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;

class FindOldExecutionsOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    protected $endProcess = false;

    /**
     * FindOldExecutions constructor.
     * @param EtlExecutionRepository $etlExecutionRepository
     * @param string $minKeep
     */
    public function __construct(private readonly FindOldExecutionConfig $config, private readonly EtlExecutionRepository $etlExecutionRepository)
    {}

    protected function processStop(StopItem $item, ExecutionContext $context): ItemInterface
    {
        if ($this->endProcess) {
            return $item;
        }

        $this->endProcess = true;
        $iterator = $this->etlExecutionRepository->getOldExecutions($this->config->minKeep);

        return new GroupedItem($iterator);
    }
}
