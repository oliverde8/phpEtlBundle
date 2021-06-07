<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationFactory\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup\DeleteEntityForOldExecutionOperation;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Symfony\Component\Validator\Constraints as Assert;

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

    /**
     * Build an operation of a certain type with the options.
     *
     * @param String $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     */
    protected function build($operation, $options)
    {
        return $this->create($this->em);
    }
}
