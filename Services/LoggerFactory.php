<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Services;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Monolog\MercureLogHandler;
use Oliverde8\PhpEtlBundle\Observability\ExecutionStatePublisherInterface;
use Oliverde8\PhpEtlBundle\Observability\NullExecutionStatePublisher;
use Psr\Log\LoggerInterface;

class LoggerFactory implements LoggerFactoryInterface
{   public function __construct(
        private readonly ChainWorkDirManager $chainWorkDirManager,
        private readonly LoggerInterface $etlLogger,
        private readonly ExecutionStatePublisherInterface $statePublisher = new NullExecutionStatePublisher(),
    )
    {
    }

    public function get(EtlExecution $execution): LoggerInterface
    {
        $logger = new Logger('etl');
        $logPath = $this->chainWorkDirManager->getLocalTmpWorkDir($execution);
        $logger->pushHandler(new StreamHandler("$logPath/execution.log", Logger::INFO));

        // Also stream log lines to the real-time hub (no-op unless e.g. Mercure is wired),
        // so the graph's log tail updates live rather than only on the next poll.
        if ($this->statePublisher->isEnabled()) {
            $mercureHandler = new MercureLogHandler($this->statePublisher, $execution, Logger::INFO);
            $mercureHandler->setFormatter(new LineFormatter("%channel%.%level_name%: %message%\n"));
            $logger->pushHandler($mercureHandler);
        }

        if ($this->etlLogger instanceof Logger) {
            foreach ($this->etlLogger->getHandlers() as $handler) {
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    }

}
