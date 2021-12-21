<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationFactory\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup\DeleteEntityForOldExecutionOperation;

class DeleteEntityForOldExecutionFactory extends AbstractFactory
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

        $this->operation = 'Etl/Cleanup/DeleteEntityForOldExecution';
        $this->class = DeleteEntityForOldExecutionOperation::class;
    }

    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create($this->em);
    }
}
