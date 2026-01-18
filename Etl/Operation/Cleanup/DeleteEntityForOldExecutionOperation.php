<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteEntityForOldExecutionConfig;

class DeleteEntityForOldExecutionOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly DeleteEntityForOldExecutionConfig $config,
        private readonly EntityManagerInterface $em)
    {}

    protected function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        /** @var EtlExecution $entity */
        $entity = $item->getData();

        $this->em->remove($entity);
        $this->em->flush();
        // Method is currently deprecated but has been un-deprecated in doctrine 3.
        $this->em->detach($entity);

        return $item;
    }
}
