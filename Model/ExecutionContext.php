<?php

namespace Oliverde8\PhpEtlBundle\Model;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

class ExecutionContext extends \Oliverde8\Component\PhpEtl\Model\ExecutionContext
{
    protected LoggerInterface $logger;

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
