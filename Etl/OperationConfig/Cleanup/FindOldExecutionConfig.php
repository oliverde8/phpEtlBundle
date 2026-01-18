<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class FindOldExecutionConfig extends AbstractOperationConfig
{
    public function __construct(public readonly \DateTime $minKeep, string $flavor = 'default')
    {
        parent::__construct($flavor);
    }
    protected function validate(bool $constructOnly): void
    {
    }
}
