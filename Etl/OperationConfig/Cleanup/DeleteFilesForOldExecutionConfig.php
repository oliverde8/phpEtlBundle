<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup;

use Oliverde8\Component\PhpEtl\OperationConfig\AbstractOperationConfig;

class DeleteFilesForOldExecutionConfig extends AbstractOperationConfig
{
    protected function validate(bool $constructOnly): void {}
}
