<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\PhpEtlBundle\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Model\LoggerProxy;

class ExecutionContextFactory implements ExecutionContextFactoryInterface
{
    /**
     * @param ChainWorkDirManager $chainWorkDirManager
     * @param LoggerFactory $loggerFactory
     * @param FileSystemFactory $fileSystemFactory
     */
    public function __construct(
        private readonly ChainWorkDirManager $chainWorkDirManager,
        private readonly LoggerFactory $loggerFactory,
        private readonly FileSystemFactory $fileSystemFactory
    ) {
    }


    public function get(array $parameters): ExecutionContext
    {
        $logger = new LoggerProxy($this->loggerFactory->get($parameters['etl']['execution']));
        $fileSystem = $this->fileSystemFactory->get($parameters['etl']['execution']);
        $workDir = $this->chainWorkDirManager->getLocalTmpWorkDir($parameters['etl']['execution']);

        $context = new ExecutionContext($parameters, $fileSystem, $logger, $workDir);
        $logger->setExecutionContext($context);

        return $context;
    }
}
