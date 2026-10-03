<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Graph;

/**
 * Normalises the per-operation run-state (the ETL "stepStats") into a clean,
 * node-id-keyed map that any frontend can overlay on a {@see ChainGraph}.
 *
 * Input is the JSON produced by json_encode()-ing the core OperationState[]
 * (as persisted on EtlExecution::stepStats, or captured live from the chain
 * observer). Node ids are derived with the SAME dotted-path scheme as
 * {@see ChainGraphBuilder}, so the two always align — including split branches
 * (OperationState::subStates is keyed identically to
 * ChainSplitOperation::getChainProcessors()).
 *
 * Output shape per node:
 *   {state, name, in, out, ms, async}
 * decoupling the wire format from php-etl's internal OperationState keys.
 */
final class RunStateNormalizer
{
    /**
     * @param array<int|string, mixed>|string|null $operationStates decoded stepStats, a JSON string, or null
     *
     * @return array<string, array{state: string, name: string, in: int, out: int, ms: int, async: int}>
     */
    public function normalize(array|string|null $operationStates): array
    {
        if (is_string($operationStates)) {
            $operationStates = json_decode($operationStates, true);
        }

        if (!is_array($operationStates)) {
            return [];
        }

        $out = [];
        $this->walk($operationStates, '', $out);

        return $out;
    }

    /**
     * @param array<int|string, mixed>                                                               $states
     * @param array<string, array{state: string, name: string, in: int, out: int, ms: int, async: int}> $out
     */
    private function walk(array $states, string $prefix, array &$out): void
    {
        foreach ($states as $index => $state) {
            if (!is_array($state)) {
                continue;
            }

            $id = '' === $prefix ? (string) $index : "$prefix.$index";

            $out[$id] = [
                'state' => (string) ($state['state'] ?? 'Waiting'),
                'name' => (string) ($state['operationName'] ?? ''),
                'in' => (int) ($state['itemsProcessed'] ?? 0),
                'out' => (int) ($state['itemsReturned'] ?? 0),
                'ms' => (int) ($state['timeSpent'] ?? 0),
                // Note: the core serialises the async count under the (misspelled) "asynInProgres" key.
                'async' => (int) ($state['asynInProgres'] ?? 0),
            ];

            if (!empty($state['subStates']) && is_array($state['subStates'])) {
                foreach ($state['subStates'] as $branch => $subStates) {
                    if (is_array($subStates)) {
                        $this->walk($subStates, "$id.$branch", $out);
                    }
                }
            }
        }
    }
}
