<?php

namespace Oliverde8\PhpEtlBundle\Model;

use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

class ExecutionContext extends \Oliverde8\Component\PhpEtl\Model\ExecutionContext
{
    public function __construct(array $parameters, FileSystemInterface $fileSystem, LoggerInterface $logger)
    {
        parent::__construct($parameters, $fileSystem);
        $this->logger = $logger;
    }
}
