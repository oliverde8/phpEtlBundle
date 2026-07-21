<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Observability;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Graph\RunStateNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publishes run-state and log updates to a Mercure hub. Registered only when
 * symfony/mercure is installed (see the bundle extension + services-mercure.yml).
 *
 * Topics are published as private updates: the Mercure hub is a separate
 * connection the browser opens directly, which bypasses the host app's
 * firewall entirely — a public topic with a predictable name (the execution's
 * id) would let anyone who can reach the hub watch every execution's live
 * state and logs. {@see authorizeSubscriber()} mints a subscriber cookie
 * scoped to one execution's topic, so only someone who already loaded the
 * (firewalled) page for that execution can subscribe.
 *
 * Every publish is best-effort: a hub error must never break the ETL run.
 */
final class MercureExecutionStatePublisher implements ExecutionStatePublisherInterface
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly RunStateNormalizer $normalizer,
        private readonly Authorization $authorization,
    ) {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function topic(EtlExecution $execution): string
    {
        return sprintf('oliverde8/etl/execution/%s', $execution->getId());
    }

    public function publicUrl(): ?string
    {
        return $this->hub->getPublicUrl();
    }

    public function authorizeSubscriber(Request $request, EtlExecution $execution): void
    {
        $this->authorization->setCookie($request, subscribe: $this->topic($execution));
    }

    public function publishState(EtlExecution $execution, array $operationStates, bool $finished): void
    {
        $normalized = $this->normalizer->normalize(json_decode(json_encode($operationStates), true));

        $this->publish($execution, [
            'state' => $normalized,
            'status' => $execution->getStatus(),
            'finished' => $finished,
        ]);
    }

    public function publishLog(EtlExecution $execution, string $line): void
    {
        $this->publish($execution, ['log' => $line]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function publish(EtlExecution $execution, array $payload): void
    {
        try {
            $this->hub->publish(new Update($this->topic($execution), (string) json_encode($payload), private: true));
        } catch (\Throwable) {
            // Observability must never break the ETL run.
        }
    }
}
