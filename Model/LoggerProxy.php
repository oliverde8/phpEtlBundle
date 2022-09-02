<?php

namespace Oliverde8\PhpEtlBundle\Model;

use Psr\Log\LoggerInterface;

class LoggerProxy implements LoggerInterface
{
    private LoggerInterface $logger;

    private ExecutionContext $executionContext;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ExecutionContext $executionContext
     */
    public function setExecutionContext(ExecutionContext $executionContext): void
    {
        $this->executionContext = $executionContext;
    }

    public function emergency($message, array $context = array()): void
    {
        $this->logger->emergency($message, $this->executionContext->getLoggerContext($context));
    }

    public function alert($message, array $context = array()): void
    {
        $this->logger->alert($message, $this->executionContext->getLoggerContext($context));
    }

    public function critical($message, array $context = array()): void
    {
        $this->logger->critical($message, $this->executionContext->getLoggerContext($context));
    }

    public function error($message, array $context = array()): void
    {
        $this->logger->error($message, $this->executionContext->getLoggerContext($context));
    }

    public function warning($message, array $context = array()): void
    {
        $this->logger->warning($message, $this->executionContext->getLoggerContext($context));
    }

    public function notice($message, array $context = array()): void
    {
        $this->logger->notice($message, $this->executionContext->getLoggerContext($context));
    }

    public function info($message, array $context = array()): void
    {
        $this->logger->info($message, $this->executionContext->getLoggerContext($context));
    }

    public function debug($message, array $context = array()): void
    {
        $this->logger->debug($message, $this->executionContext->getLoggerContext($context));
    }

    public function log($level, $message, array $context = array()): void
    {
        $this->logger->log($level, $message, $this->executionContext->getLoggerContext($context));
    }
}
