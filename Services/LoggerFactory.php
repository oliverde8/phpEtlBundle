<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Services;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Psr\Log\LoggerInterface;

class LoggerFactory implements LoggerFactoryInterface
{
    public function __construct(private readonly ChainWorkDirManager $chainWorkDirManager, private readonly LoggerInterface $etlLogger)
    {
    }

    public function get(EtlExecution $execution): LoggerInterface
    {
        $logger = new Logger('etl');
        $logPath = $this->chainWorkDirManager->getLocalTmpWorkDir($execution);
        $logger->pushHandler(new StreamHandler("$logPath/execution.log", Logger::INFO));

        if ($this->etlLogger instanceof Logger) {
            foreach ($this->etlLogger->getHandlers() as $handler) {
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    }

}
