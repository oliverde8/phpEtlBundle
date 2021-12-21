<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;

class DeleteEntityForOldExecutionOperation extends AbstractChainOperation
{
    protected EntityManagerInterface $em;

    /**
     * DeleteEntityForOldExecutionOperation constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

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
