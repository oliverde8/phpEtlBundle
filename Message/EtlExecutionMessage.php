<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Message;

class EtlExecutionMessage
{
    /** @var int */
    private $id;

    /**
     * EtlExecutionMessage constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
