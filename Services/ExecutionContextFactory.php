<?php

namespace Oliverde8\PhpEtlBundle\Services;

use Monolog\Logger;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;

class ExecutionContextFactory implements ExecutionContextFactoryInterface
{
    private LoggerFactory $loggerFactory;

    private FileSystemFactory $fileSystemFactory;

    public function __construct(LoggerFactory $loggerFactory, FileSystemFactory $fileSystemFactory)
    {
        $this->loggerFactory = $loggerFactory;
        $this->fileSystemFactory = $fileSystemFactory;
    }


    public function get(array $parameters): ExecutionContext
    {
        $logger = $this->loggerFactory->get($parameters['etl']['execution']);
        $fileSystem = $this->fileSystemFactory->get($parameters['etl']['execution']);
        return new ExecutionContext($parameters, $fileSystem, $logger);
    }
}
