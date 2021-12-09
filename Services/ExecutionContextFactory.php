<?php

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\PhpEtlBundle\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Model\LoggerProxy;

class ExecutionContextFactory implements ExecutionContextFactoryInterface
{
    private ChainWorkDirManager $chainWorkDirManager;

    private LoggerFactory $loggerFactory;

    private FileSystemFactory $fileSystemFactory;

    /**
     * @param ChainWorkDirManager $chainWorkDirManager
     * @param LoggerFactory $loggerFactory
     * @param FileSystemFactory $fileSystemFactory
     */
    public function __construct(
        ChainWorkDirManager $chainWorkDirManager,
        LoggerFactory $loggerFactory,
        FileSystemFactory $fileSystemFactory
    ) {
        $this->chainWorkDirManager = $chainWorkDirManager;
        $this->loggerFactory = $loggerFactory;
        $this->fileSystemFactory = $fileSystemFactory;
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
