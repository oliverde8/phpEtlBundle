<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationFactory\Cleanup;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup\DeleteFilesForOldExecutionOperation;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;
use Oliverde8\PhpEtlBundle\Services\FileSystemFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteFilesForOldExecutionFactory extends AbstractFactory
{
    /** @var ChainWorkDirManager */
    protected $chainWorkdDirManager;

    protected FileSystemFactoryInterface $fileSystemFactory;

    public function __construct(ChainWorkDirManager $chainWorkdDirManager, FileSystemFactoryInterface $fileSystemFactory)
    {
        $this->chainWorkdDirManager = $chainWorkdDirManager;
        $this->fileSystemFactory = $fileSystemFactory;

        $this->operation = 'Etl/Cleanup/DeleteFilesForOldExecution';
        $this->class = DeleteFilesForOldExecutionOperation::class;
    }

    protected function build(string $operation, array $options): ChainOperationInterface
    {
        return $this->create($this->chainWorkdDirManager, $this->fileSystemFactory);
    }
}
