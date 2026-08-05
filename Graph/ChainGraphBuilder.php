<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Graph;

use Oliverde8\Component\PhpEtl\ChainOperation\SubChainsAwareOperationInterface;
use Oliverde8\Component\PhpEtl\ChainProcessorInterface;

/**
 * Builds the static {@see ChainGraph} topology from a chain processor.
 *
 * Mirrors the traversal of the core MermaidStaticOutput (php-etl) but emits a
 * framework-agnostic node/edge structure instead of Mermaid text, and uses
 * dotted-path node ids that match {@see RunStateNormalizer} so live/persisted
 * run-state can be overlaid on the topology.
 *
 * Edge semantics follow the core: within a chain, link N connects to link N+1;
 * a branch-holding operation (split, merge, ...) connects to the first node of
 * each branch, and the main line continues from that operation's own node
 * (branches do not rejoin automatically).
 *
 * {@see SubChainsAwareOperationInterface} is only implemented by php-etl 2.1+
 * (Split and Merge so far). Checking `instanceof` against it is safe even when
 * the bundle runs against php-etl 2.0 — PHP evaluates instanceof as false for
 * a non-existent class rather than erroring — so this bundle keeps working
 * with either version; branch-holding operations older than 2.1 just render
 * as a single opaque node, same as before.
 */
final class ChainGraphBuilder
{
    public function build(ChainProcessorInterface $processor): ChainGraph
    {
        $graph = new ChainGraph();
        $this->addNodes($processor, '', $graph);
        $this->addEdges($processor, '', null, $graph);

        return $graph;
    }

    private function addNodes(ChainProcessorInterface $processor, string $prefix, ChainGraph $graph): void
    {
        $names = $processor->getChainLinkNames();

        foreach ($processor->getChainLinks() as $index => $link) {
            $id = '' === $prefix ? (string) $index : "$prefix.$index";
            $isBranching = $link instanceof SubChainsAwareOperationInterface;
            $type = $this->typeOf($link);

            // ChainConfig::addLink() keys named links by name and unnamed links by a
            // separate auto-increment counter, so an unnamed link's key does not
            // necessarily equal its position here — checking is_string() (rather than
            // comparing to the position) is the only reliable way to tell "this is a
            // real name" from "this is an auto-assigned index", named or not.
            $rawName = $names[$index] ?? null;
            $name = is_string($rawName) ? $rawName : $type;

            $graph->addNode(new GraphNode(
                $id,
                $name,
                $type,
                $isBranching ? GraphNode::KIND_SPLIT : GraphNode::KIND_OPERATION,
            ));

            if ($isBranching) {
                foreach ($link->getChainProcessors() as $branch => $subProcessor) {
                    $this->addNodes($subProcessor, "$id.$branch", $graph);
                }
            }
        }
    }

    private function addEdges(ChainProcessorInterface $processor, string $prefix, ?string $previous, ChainGraph $graph): void
    {
        foreach ($processor->getChainLinks() as $index => $link) {
            $id = '' === $prefix ? (string) $index : "$prefix.$index";

            if (null !== $previous) {
                $graph->addEdge($previous, $id);
            }
            $previous = $id;

            if ($link instanceof SubChainsAwareOperationInterface) {
                // Each branch starts from this node; the main line ($previous) stays
                // on it so the next top-level link follows from here.
                foreach ($link->getChainProcessors() as $branch => $subProcessor) {
                    $this->addEdges($subProcessor, "$id.$branch", $id, $graph);
                }
            }
        }
    }

    private function typeOf(object $operation): string
    {
        $parts = explode('\\', $operation::class);

        return end($parts);
    }
}
