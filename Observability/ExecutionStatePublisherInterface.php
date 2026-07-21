<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Observability;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pushes live execution state/logs to subscribers (e.g. a Mercure hub) so the
 * graph updates in real time.
 *
 * Optional by design: when no real-time transport is installed the bundle binds
 * {@see NullExecutionStatePublisher} (a no-op), so callers can always depend on
 * this interface and guard the cost of building payloads with {@see isEnabled()}.
 */
interface ExecutionStatePublisherInterface
{
    /**
     * Whether pushing is actually wired. Callers should skip building payloads
     * when this returns false.
     */
    public function isEnabled(): bool;

    /**
     * Per-execution topic the frontend subscribes to.
     */
    public function topic(EtlExecution $execution): string;

    /**
     * Browser-facing hub URL to subscribe to, or null when unavailable.
     */
    public function publicUrl(): ?string;

    /**
     * Authorizes the current browser to subscribe to this execution's topic
     * (e.g. sets a Mercure subscriber cookie scoped to that one topic).
     *
     * Updates are published as private topics, so without this the browser
     * simply can't subscribe — call it once the caller has already verified
     * the current user is allowed to view this execution; this method does
     * not perform any authorization check itself.
     */
    public function authorizeSubscriber(Request $request, EtlExecution $execution): void;

    /**
     * Push a run-state snapshot.
     *
     * @param array<int|string, mixed> $operationStates raw (JSON-serialisable) OperationState[] from the chain observer, or decoded stepStats
     */
    public function publishState(EtlExecution $execution, array $operationStates, bool $finished): void;

    /**
     * Push a single log line.
     */
    public function publishLog(EtlExecution $execution, string $line): void;
}
