<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Observability;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default no-op publisher used when no real-time transport (e.g. Mercure) is
 * installed. The graph then falls back to polling / static rendering.
 */
final class NullExecutionStatePublisher implements ExecutionStatePublisherInterface
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function topic(EtlExecution $execution): string
    {
        return sprintf('oliverde8/etl/execution/%s', $execution->getId());
    }

    public function publicUrl(): ?string
    {
        return null;
    }

    public function authorizeSubscriber(Request $request, EtlExecution $execution): void
    {
    }

    public function publishState(EtlExecution $execution, array $operationStates, bool $finished): void
    {
    }

    public function publishLog(EtlExecution $execution, string $line): void
    {
    }
}
