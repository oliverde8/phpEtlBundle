<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class DeleteEntityForOldExecutionConfig extends AbstractOperationConfig
{
    protected function validate(bool $constructOnly): void
    {}
}
