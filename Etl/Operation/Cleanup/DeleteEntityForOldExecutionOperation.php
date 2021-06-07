<?php


namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;


use Doctrine\ORM\EntityManagerInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;

class DeleteEntityForOldExecutionOperation extends AbstractChainOperation
{
    /** @var EntityManagerInterface  */
    protected EntityManagerInterface $em;

    /**
     * DeleteEntityForOldExecutionOperation constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function processData(ItemInterface $item, array &$context)
    {
        /** @var EtlExecution $entity */
        $entity = $item->getData();

        $this->em->remove($entity);
        $this->em->flush();
        // Method is currently deprecated but has been un-deprecated it doctrine 3.
        $this->em->detach($entity);
    }

}