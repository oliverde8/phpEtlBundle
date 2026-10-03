<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Observability\ExecutionStatePublisherInterface;

/**
 * Monolog handler that streams an execution's log lines to the real-time
 * publisher (e.g. Mercure), so the graph's log tail updates instantly rather
 * than only on the next poll. Depends solely on the bundle's publisher
 * interface — no hard Mercure dependency — so it is safe to instantiate
 * whenever a publisher is enabled.
 */
final class MercureLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly ExecutionStatePublisherInterface $publisher,
        private readonly EtlExecution $execution,
        int|string|Level $level = Level::Info,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $line = trim((string) $record->formatted);
        if ('' !== $line) {
            $this->publisher->publishLog($this->execution, $line);
        }
    }
}
