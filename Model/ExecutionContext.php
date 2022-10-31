<?php

namespace Oliverde8\PhpEtlBundle\Model;

use Monolog\Logger;
use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\Component\PhpEtl\Model\File\LocalFileSystem;
use Psr\Log\LoggerInterface;

class ExecutionContext extends \Oliverde8\Component\PhpEtl\Model\ExecutionContext
{
    protected string $workDir;

    public function __construct(array $parameters, FileSystemInterface $fileSystem, LoggerInterface $logger, string $logPath)
    {
        parent::__construct($parameters, $fileSystem);
        $this->logger = $logger;
        $this->workDir = $logPath;
    }

    protected function finalise(): void
    {
        if ($this->logger instanceof Logger) {
            foreach ($this->logger->getHandlers() as $handler) {
                $handler->close();
            }
        }

        if ($this->fileSystem instanceof LocalFileSystem && $this->fileSystem->getRootPath() == $this->workDir) {
            // Local file system needs no moving of the log file.
            return;
        }

        $logPath = $this->workDir . "/execution.log";
        if(file_exists($logPath)) {
            $this->fileSystem->writeStream("execution.log", fopen($logPath, 'r'));
        }
    }
}
