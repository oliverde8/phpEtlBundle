<?php

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\Item\GroupedItem;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;

class FindOldExecutionsOperation extends AbstractChainOperation
{
    protected EtlExecutionRepository $etlExecutionRepository;

    protected \DateTime $minKeep;

    protected $endProcess = false;

    /**
     * FindOldExecutions constructor.
     * @param EtlExecutionRepository $etlExecutionRepository
     * @param string $minKeep
     */
    public function __construct(EtlExecutionRepository $etlExecutionRepository, \DateTime $minKeep)
    {
        $this->etlExecutionRepository = $etlExecutionRepository;
        $this->minKeep = $minKeep;
    }

    protected function processStop(ItemInterface $item, ExecutionContext $context): ItemInterface
    {
        if ($this->endProcess) {
            return $item;
        }

        $this->endProcess = true;
        $iterator = $this->etlExecutionRepository->getOldExecutions($this->minKeep);

        return new GroupedItem($iterator);
    }
}