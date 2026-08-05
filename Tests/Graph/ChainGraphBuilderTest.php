<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Tests\Graph;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainMergeOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactoryInterface;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainMergeConfig;
use Oliverde8\Component\PhpEtl\OperationConfig\ChainSplitConfig;
use Oliverde8\PhpEtlBundle\Graph\ChainGraph;
use Oliverde8\PhpEtlBundle\Graph\ChainGraphBuilder;
use Oliverde8\PhpEtlBundle\Graph\GraphNode;
use Oliverde8\PhpEtlBundle\Graph\RunStateNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * The whole live-graph feature relies on one invariant: the static topology
 * node ids ({@see ChainGraphBuilder}) and the run-state node ids
 * ({@see RunStateNormalizer}) must be derived identically, including for the
 * nested branches of a split. If they ever drift, live state silently stops
 * lining up with the graph. This test pins that invariant.
 */
class ChainGraphBuilderTest extends TestCase
{
    public function testLinearChainIdsNamesAndEdges(): void
    {
        $processor = new ChainProcessor([
            'read' => $this->createMock(ChainOperationInterface::class),
            'transform' => $this->createMock(ChainOperationInterface::class),
            'write' => $this->createMock(ChainOperationInterface::class),
        ], $this->createMock(ExecutionContextFactoryInterface::class));

        $graph = (new ChainGraphBuilder())->build($processor);

        self::assertSame(['0', '1', '2'], $this->sortedNodeIds($graph));
        self::assertSame([['0', '1'], ['1', '2']], $this->edgePairs($graph));
        self::assertSame('read', $graph->getNodes()[0]->name);
        self::assertSame(GraphNode::KIND_OPERATION, $graph->getNodes()[0]->kind);
    }

    public function testSplitTopologyIdsMatchRunStateIds(): void
    {
        $ctxFactory = $this->createMock(ExecutionContextFactoryInterface::class);

        $branchA = new ChainProcessor([
            'mapA0' => $this->createMock(ChainOperationInterface::class),
            'mapA1' => $this->createMock(ChainOperationInterface::class),
        ], $ctxFactory);
        $branchB = new ChainProcessor([
            'mapB0' => $this->createMock(ChainOperationInterface::class),
        ], $ctxFactory);

        // ChainSplitOperation asks the builder to create one sub-processor per split config.
        $chainBuilder = $this->createMock(ChainBuilderV2::class);
        $branches = [$branchA, $branchB];
        $next = 0;
        $chainBuilder->method('createChain')->willReturnCallback(
            static function () use (&$next, $branches) {
                return $branches[$next++];
            }
        );

        $splitConfig = (new ChainSplitConfig())
            ->addSplit(new ChainConfig())
            ->addSplit(new ChainConfig());
        $split = new ChainSplitOperation($chainBuilder, $splitConfig);

        $processor = new ChainProcessor([
            'read' => $this->createMock(ChainOperationInterface::class),
            'split' => $split,
            'write' => $this->createMock(ChainOperationInterface::class),
        ], $ctxFactory);

        // Static topology.
        $graph = (new ChainGraphBuilder())->build($processor);
        $topologyIds = $this->sortedNodeIds($graph);

        // Run-state: the initial observer snapshot already carries the split's
        // nested sub-states, so no items need to be processed.
        $states = $processor->initObserver()->getOperationStates();
        $decoded = json_decode(json_encode($states), true);
        // Cast keys to string: PHP coerces canonical-integer array keys to int,
        // but on the wire (JSON object keys) they are always strings — as are the
        // topology GraphNode ids we compare against.
        $runStateIds = array_map('strval', array_keys((new RunStateNormalizer())->normalize($decoded)));
        sort($runStateIds, SORT_STRING);

        self::assertSame(
            ['0', '1', '1.0.0', '1.0.1', '1.1.0', '2'],
            $topologyIds,
            'Topology ids (incl. split branches) are not as expected',
        );
        self::assertSame(
            $topologyIds,
            $runStateIds,
            'Run-state node ids must exactly match the topology node ids',
        );

        $splitNode = $this->nodeById($graph, '1');
        self::assertSame(GraphNode::KIND_SPLIT, $splitNode->kind);
        self::assertSame('split', $splitNode->name);
    }

    public function testMergeBranchesAreVisibleInTheTopology(): void
    {
        $ctxFactory = $this->createMock(ExecutionContextFactoryInterface::class);

        $branchA = new ChainProcessor(['mapA0' => $this->createMock(ChainOperationInterface::class)], $ctxFactory);
        $branchB = new ChainProcessor(['mapB0' => $this->createMock(ChainOperationInterface::class)], $ctxFactory);

        $chainBuilder = $this->createMock(ChainBuilderV2::class);
        $branches = [$branchA, $branchB];
        $next = 0;
        $chainBuilder->method('createChain')->willReturnCallback(
            static function () use (&$next, $branches) {
                return $branches[$next++];
            }
        );

        $mergeConfig = (new ChainMergeConfig())
            ->addMerge(new ChainConfig())
            ->addMerge(new ChainConfig());
        $merge = new ChainMergeOperation($chainBuilder, $mergeConfig);

        $processor = new ChainProcessor([
            'read' => $this->createMock(ChainOperationInterface::class),
            'merge' => $merge,
            'write' => $this->createMock(ChainOperationInterface::class),
        ], $ctxFactory);

        $graph = (new ChainGraphBuilder())->build($processor);

        self::assertSame(
            ['0', '1', '1.0.0', '1.1.0', '2'],
            $this->sortedNodeIds($graph),
            'Merge branches must be visible in the topology, same as split branches'
        );

        $mergeNode = $this->nodeById($graph, '1');
        self::assertSame(GraphNode::KIND_SPLIT, $mergeNode->kind);
        self::assertSame('merge', $mergeNode->name);
    }

    public function testAnAutoAssignedKeyIsNeverShownAsIfItWereAName(): void
    {
        // Mirrors what ChainConfig::addLink() actually produces once a named link
        // precedes an unnamed one: the unnamed link's key is an int, but it no
        // longer equals its position (here: key 0, position 1).
        $processor = new ChainProcessor([
            'extract' => $this->createMock(ChainOperationInterface::class),
            0 => $this->createMock(ChainOperationInterface::class),
        ], $this->createMock(ExecutionContextFactoryInterface::class));

        $graph = (new ChainGraphBuilder())->build($processor);

        $unnamed = $this->nodeById($graph, '1');
        self::assertNotSame('0', $unnamed->name);
        self::assertSame($unnamed->type, $unnamed->name);
    }

    /** @return string[] */
    private function sortedNodeIds(ChainGraph $graph): array
    {
        $ids = array_map(static fn (GraphNode $n): string => $n->id, $graph->getNodes());
        sort($ids, SORT_STRING);

        return $ids;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function edgePairs(ChainGraph $graph): array
    {
        return array_map(static fn (array $e): array => [$e['source'], $e['target']], $graph->getEdges());
    }

    private function nodeById(ChainGraph $graph, string $id): GraphNode
    {
        foreach ($graph->getNodes() as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        self::fail("No node with id $id");
    }
}
