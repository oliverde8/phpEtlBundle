<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Model;

use Psr\Log\LoggerInterface;

class LoggerProxy implements LoggerInterface
{
    private ExecutionContext $executionContext;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param ExecutionContext $executionContext
     */
    public function setExecutionContext(ExecutionContext $executionContext): void
    {
        $this->executionContext = $executionContext;
    }

    public function emergency(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->emergency($message, $this->executionContext->getLoggerContext($context));
    }

    public function alert(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->alert($message, $this->executionContext->getLoggerContext($context));
    }

    public function critical(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->critical($message, $this->executionContext->getLoggerContext($context));
    }

    public function error(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->error($message, $this->executionContext->getLoggerContext($context));
    }

    public function warning(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->warning($message, $this->executionContext->getLoggerContext($context));
    }

    public function notice(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->notice($message, $this->executionContext->getLoggerContext($context));
    }

    public function info(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->info($message, $this->executionContext->getLoggerContext($context));
    }

    public function debug(string|\Stringable $message, array $context = array()): void
    {
        $this->logger->debug($message, $this->executionContext->getLoggerContext($context));
    }

    public function log($level, string|\Stringable $message, array $context = array()): void
    {
        $this->logger->log($level, $message, $this->executionContext->getLoggerContext($context));
    }
}
