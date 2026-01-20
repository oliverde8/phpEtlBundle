<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\ChainBuilderV2;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use Oliverde8\PhpEtlBundle\Exception\UnknownChainException;
use Oliverde8\PhpEtlBundle\Factory\ChainFactory;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function Clue\StreamFilter\fun;

class ChainProcessorsManager
{
    public function __construct(
        protected readonly EtlExecutionRepository $etlExecutionRepository,
        protected readonly LoggerFactory $loggerFactory,
        protected readonly ChainFactory $chainFactory,
        protected readonly array $definitions,
        protected readonly array $rawDefinitions,
        #[AutowireIterator('etl.chain_definition')]
        /** @var ChainDefinitionInterface[] */
        protected readonly iterable $v2ChainDefinitions,
    ) {}

    /**
     * @throws UnknownChainException
     */
    public function getRawDefinition(string $chainName): string | ChainDefinitionInterface
    {
        foreach ($this->v2ChainDefinitions as $v2ChainDefinition) {
            if ($v2ChainDefinition->getKey() === $chainName) {
                return $v2ChainDefinition;
            }
        }
        if (!isset($this->rawDefinitions[$chainName])) {
            $alternatives = [];
            foreach (array_keys($this->rawDefinitions) as $knownId) {
                $lev = levenshtein($chainName, $knownId);
                if ($lev <= \strlen($chainName) / 3 || str_contains($knownId, $chainName)) {
                    $alternatives[] = $knownId;
                }
            }

            throw new UnknownChainException("Unknown chain '$chainName', did you mean: " . implode(", ", $alternatives));
        }

        return $this->rawDefinitions[$chainName];
    }

    public function getRawDefinitions(): array
    {
        return $this->rawDefinitions;
    }

    public function getProcessor(string $chainName, array $options): ChainProcessor
    {
        $definition = $this->getRawDefinition($chainName);
        if (!is_string($definition)) {
            return $this->chainFactory->createFromDefinition($definition);
        }
        $definition = $this->definitions[$chainName];
        $chain = $definition['chain'];
        $maxAsynchronousItems = $definition['maxAsynchronousItems'] ?? 20;
        $defaultOptions = $definition['defaultOptions'] ?? [];

        $options = array_merge($defaultOptions, $options);

        return $this->chainFactory->create($chain, $options, $maxAsynchronousItems);
    }

    /**
     * Execute a particular chanin
     *
     * @param string $chainName
     * @param iterable $iterator
     * @param array $params
     *
     * @throws \Exception
     */
    public function execute(string $chainName, iterable $iterator, array $params,  ?callable $observerCallback = null)
    {
        $definition = $this->getRawDefinition($chainName);
        $definitionArray = $this->definitions[$chainName] ?? [];

        $inputData = ["Iterator! Can't show input data"];
        if (is_array($iterator) && empty($iterator) && isset($definitionArray['defaultInput'])) {
            $inputData = $definitionArray['defaultInput'];
            $iterator = new \ArrayIterator($definitionArray['defaultInput']);
        } elseif (is_array($iterator)) {
            $inputData = $iterator;
            $iterator = new \ArrayIterator($iterator);
        }

        $execution = new EtlExecution($chainName, "", $inputData, $params);
        $execution->setStatus(EtlExecution::STATUS_RUNNING);
        $this->etlExecutionRepository->save($execution);

        $this->executeFromEtlEntity($execution, $iterator, $observerCallback);
    }

    /**
     * Execute a chain from it's entity.
     *
     */
    public function executeFromEtlEntity(EtlExecution $execution, iterable $iterator = null, ?callable $observerCallback = null): void
    {
        $chainName = $execution->getName();
        $logger = $this->loggerFactory->get($execution);

        try {
            // Update execution object with new status.
            $execution->setStatus(EtlExecution::STATUS_RUNNING);
            $execution->setStartTime(new \DateTime());
            $execution->setWaitTime(time() - $execution->getCreateTime()->getTimestamp());
            $this->etlExecutionRepository->save($execution);

            // Build the processor.
            $params = json_decode($execution->getInputOptions(), true);
            $processor = $this->getProcessor($chainName, $params);

            if (is_null($iterator)) {
                $iterator = new \ArrayIterator(json_decode($execution->getInputData(), true));
            }
            $params['etl'] = [
                'chain' => $chainName,
                'startTime' => new \DateTime(),
                'execution' => $execution
            ];

            // Start the process.
            $observerProcessTime = 0;
            $processor->process($iterator, $params, function (array $operationStates, int $processedItems, int $returnedItems, bool $hasFinished = false) use ($observerCallback, &$observerProcessTime, $execution) {
                if ($observerCallback) {
                    $observerCallback($operationStates, $processedItems, $returnedItems, $hasFinished);
                }

                if ((time() - $observerProcessTime) > 5 || $hasFinished) {
                    $jsonStates = json_encode($operationStates);
                    $execution->setStepStats($jsonStates);
                    $this->etlExecutionRepository->updateStepStats($execution, $jsonStates);
                    $observerProcessTime = time();
                }
            });
            $execution = $this->etlExecutionRepository->find($execution->getId());
            $execution->setStatus(EtlExecution::STATUS_SUCCESS);
        } catch (\Throwable $exception) {
            $execution = $this->etlExecutionRepository->find($execution->getId());
            $execution->setFailTime(new \DateTime());
            $execution->setStatus(EtlExecution::STATUS_FAILURE);
            $execution->setErrorMessage($this->getFullExceptionTrace($exception));
            throw $exception;
        } finally {
            $execution->setEndTime(new \DateTime());
            $execution->setRunTime(time() - $execution->getStartTime()->getTimestamp());
            $this->etlExecutionRepository->save($execution);
        }
    }

    protected function getFullExceptionTrace(\Throwable $exception): string
    {
        $message = '';
        do {
            $message .= $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n\n";
        } while ($exception = $exception->getPrevious());

        return $message;
    }
}
