<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Graph;

/**
 * Framework-agnostic description of a chain's static topology: the nodes
 * (operations / splits) and the directed edges between them. Serialises to
 * {"nodes": [...], "edges": [{"source": id, "target": id}, ...]} for any
 * frontend (EasyAdmin, Sylius, custom) to render.
 */
final class ChainGraph implements \JsonSerializable
{
    /** @var GraphNode[] */
    private array $nodes = [];

    /** @var array<int, array{source: string, target: string}> */
    private array $edges = [];

    public function addNode(GraphNode $node): void
    {
        $this->nodes[] = $node;
    }

    public function addEdge(string $source, string $target): void
    {
        $this->edges[] = ['source' => $source, 'target' => $target];
    }

    /** @return GraphNode[] */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /** @return array<int, array{source: string, target: string}> */
    public function getEdges(): array
    {
        return $this->edges;
    }

    public function jsonSerialize(): array
    {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
        ];
    }
}
