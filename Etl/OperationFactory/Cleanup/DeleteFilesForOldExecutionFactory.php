<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationFactory\Cleanup;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup\DeleteFilesForOldExecutionOperation;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteFilesForOldExecutionFactory extends AbstractFactory
{
    /** @var ChainWorkDirManager */
    protected $chainWorkdDirManager;

    /**
     * DeleteFilesForOldExecutionOperation constructor.
     * @param ChainWorkDirManager $chainWorkdDirManager
     */
    public function __construct(ChainWorkDirManager $chainWorkdDirManager)
    {
        $this->chainWorkdDirManager = $chainWorkdDirManager;

        $this->operation = 'Etl/Cleanup/DeleteFilesForOldExecution';
        $this->class = DeleteFilesForOldExecutionOperation::class;
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
        return $this->create($this->chainWorkdDirManager);
    }
}
