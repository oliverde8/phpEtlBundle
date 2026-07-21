<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Graph;

/**
 * A single node in the framework-agnostic chain topology.
 *
 * The {@see $id} is a dotted path derived from the operation's position in the
 * chain (e.g. "1" for the second top-level link, "1.0.2" for the third link of
 * the first branch of a split at position 1). {@see ChainGraphBuilder} and
 * {@see RunStateNormalizer} MUST derive this id identically so topology nodes
 * and run-state entries line up.
 */
final class GraphNode implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $kind = self::KIND_OPERATION,
    ) {
    }

    public const KIND_OPERATION = 'operation';

    /** Any branch-holding operation (split, merge, ...) — rendered as a hexagon by the widget. */
    public const KIND_SPLIT = 'split';

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'kind' => $this->kind,
        ];
    }
}
