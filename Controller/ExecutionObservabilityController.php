<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Controller;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Graph\ChainGraphBuilder;
use Oliverde8\PhpEtlBundle\Graph\RunStateNormalizer;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Oliverde8\PhpEtlBundle\Security\EtlExecutionVoter;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;
use Oliverde8\PhpEtlBundle\Services\ExecutionContextFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Framework-native (not EasyAdmin-coupled) JSON endpoints that power the live
 * execution graph. Any Symfony frontend (EasyAdmin, Sylius, custom) mounts
 * these routes and reuses them; the shipped JS controller talks to them.
 *
 *  - GET .../graph  → static topology + the last persisted run-state (static fallback)
 *  - GET .../state  → latest persisted run-state (poll fallback when no Mercure)
 *  - GET .../logs   → incremental log tail (offset in lines)
 *
 * All three are read-only and guarded by {@see EtlExecutionVoter::VIEW}.
 */
class ExecutionObservabilityController extends AbstractController
{
    private const int LOG_BATCH = 1000;

    public function __construct(
        private readonly EtlExecutionRepository $executions,
        private readonly ChainGraphBuilder $graphBuilder,
        private readonly RunStateNormalizer $stateNormalizer,
        private readonly ChainProcessorsManager $chainProcessorManager,
        private readonly ChainWorkDirManager $workDirManager,
        private readonly ExecutionContextFactory $executionContextFactory,
    ) {
    }

    #[Route('/etl/executions/{id}/graph', name: 'oliverde8_etl_execution_graph', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function graph(int $id): JsonResponse
    {
        $execution = $this->findExecution($id);

        $data = [
            'execution' => $this->executionMeta($execution),
            'graph' => ['nodes' => [], 'edges' => []],
            'state' => $this->stateNormalizer->normalize($execution->getStepStats()),
            'error' => null,
        ];

        try {
            $processor = $this->chainProcessorManager->getProcessor($execution->getName(), $this->options($execution));
            $data['graph'] = $this->graphBuilder->build($processor);
        } catch (\Throwable $e) {
            // The definition may have changed or been removed since the run: still
            // return the (topology-less) persisted state so the UI degrades cleanly.
            $data['error'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    #[Route('/etl/executions/{id}/state', name: 'oliverde8_etl_execution_state', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function state(int $id): JsonResponse
    {
        $execution = $this->findExecution($id);

        return new JsonResponse([
            'status' => $execution->getStatus(),
            'finished' => $this->isFinished($execution),
            'state' => $this->stateNormalizer->normalize($execution->getStepStats()),
        ]);
    }

    #[Route('/etl/executions/{id}/logs', name: 'oliverde8_etl_execution_logs', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function logs(int $id, Request $request): JsonResponse
    {
        $execution = $this->findExecution($id);

        $offset = max(0, $request->query->getInt('offset'));
        $lines = $this->readLogLines($execution);
        $slice = \array_slice($lines, $offset, self::LOG_BATCH);

        return new JsonResponse([
            'lines' => array_values($slice),
            'offset' => $offset + \count($slice),
            'more' => \count($lines) > $offset + \count($slice),
            'finished' => $this->isFinished($execution),
        ]);
    }

    private function findExecution(int $id): EtlExecution
    {
        $execution = $this->executions->find($id);
        if (!$execution instanceof EtlExecution) {
            throw $this->createNotFoundException("Unknown ETL execution $id");
        }

        $this->denyAccessUnlessGranted(EtlExecutionVoter::VIEW, $execution);

        return $execution;
    }

    /** @return array<string, mixed> */
    private function options(EtlExecution $execution): array
    {
        $options = json_decode((string) $execution->getInputOptions(), true);

        return \is_array($options) ? $options : [];
    }

    private function isFinished(EtlExecution $execution): bool
    {
        return \in_array(
            $execution->getStatus(),
            [EtlExecution::STATUS_SUCCESS, EtlExecution::STATUS_FAILURE],
            true,
        );
    }

    /** @return array<string, mixed> */
    private function executionMeta(EtlExecution $execution): array
    {
        return [
            'id' => $execution->getId(),
            'name' => $execution->getName(),
            'username' => $execution->getUsername(),
            'status' => $execution->getStatus(),
            'finished' => $this->isFinished($execution),
            'createTime' => $execution->getCreateTime()->format(\DATE_ATOM),
            'startTime' => $execution->getStartTime()?->format(\DATE_ATOM),
            'endTime' => $execution->getEndTime()?->format(\DATE_ATOM),
        ];
    }

    /** @return string[] */
    private function readLogLines(EtlExecution $execution): array
    {
        // While running, the log lives in the local tmp work dir; after the
        // context is finalised it may have been copied to the execution filesystem.
        $localLog = $this->workDirManager->getLocalTmpWorkDir($execution, false).'/execution.log';
        if (is_file($localLog)) {
            $content = @file($localLog, \FILE_IGNORE_NEW_LINES);

            return false === $content ? [] : $content;
        }

        try {
            $fileSystem = $this->executionContextFactory->get(['etl' => ['execution' => $execution]])->getFileSystem();
            if ($fileSystem->fileExists('execution.log')) {
                $stream = $fileSystem->readStream('execution.log');
                $lines = [];
                while (false !== ($line = fgets($stream))) {
                    $lines[] = rtrim($line, "\r\n");
                }
                fclose($stream);

                return $lines;
            }
        } catch (\Throwable) {
            // Best-effort tail: never let a missing/remote log break the endpoint.
        }

        return [];
    }
}
