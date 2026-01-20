<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Message;

class EtlExecutionMessage
{
    public function __construct(
        private readonly int $id
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
