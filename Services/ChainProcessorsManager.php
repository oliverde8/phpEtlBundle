<?php

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Exception\ChainOperationException;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ChainProcessorsManager
{
    protected ContainerInterface $container;

    protected EtlExecutionRepository $etlExecutionRepository;

    protected LoggerFactory $loggerFactory;

    protected array $definitions;

    public function __construct(
        ContainerInterface $container,
        EtlExecutionRepository $etlExecutionRepository,
        LoggerFactory $loggerFactory,
        array $definitions
    ) {
        $this->container = $container;
        $this->etlExecutionRepository = $etlExecutionRepository;
        $this->loggerFactory = $loggerFactory;
        $this->definitions = $definitions;
    }

    public function getDefinition(string $chainName): string
    {
        return $this->definitions[$chainName];
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function getProcessor(string $chainName): ChainProcessor
    {
        // TODO Think about either creating the processor & runtime or injecting them into the constructor like the definitions.
        return $this->container->get("oliverde8.etl.chain.$chainName");
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
    public function execute(string $chainName, iterable $iterator, array $params)
    {
        $definition = $this->getDefinition($chainName);

        $inputData = ["Iterator! Can't show input data"];
        if (is_array($iterator)) {
            $inputData = $iterator;
            $iterator = new \ArrayIterator($iterator);
        }

        $execution = new EtlExecution($chainName, $definition, $inputData, $params);
        $execution->setStatus(EtlExecution::STATUS_RUNNING);
        $this->etlExecutionRepository->save($execution);

        $this->executeFromEtlEntity($execution, $iterator);
    }

    /**
     * Execute a chain from it's entity.
     *
     */
    public function executeFromEtlEntity(EtlExecution $execution, iterable $iterator = null)
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
            $processor = $this->getProcessor($chainName);
            $params = json_decode($execution->getInputOptions(), true);

            if (is_null($iterator)) {
                $iterator = new \ArrayIterator(json_decode($execution->getInputData(), true));
            }
            $params['etl'] = [
                'chain' => $chainName,
                'startTime' => new \DateTime(),
                'execution' => $execution
            ];

            // Start the process.
            $logger->info("Starting etl process!", $params);
            $processor->process($iterator, $params);
            $execution->setStatus(EtlExecution::STATUS_SUCCESS);

            $logger->info("Finished etl process!", $params);
        } catch (\Throwable $exception) {
            $params['exception'] = $exception;
            $logger->info("Failed during etl process!", $params);

            $execution->setFailTime(new \DateTime());
            $execution->setStatus(EtlExecution::STATUS_FAILURE);
            $execution->setErrorMessage($this->getFullExeptionTrace($exception));
            throw $exception;
        } finally {
            $execution->setEndTime(new \DateTime());
            $execution->setRunTime(time() - $execution->getStartTime()->getTimestamp());
            $execution->setStepStats('[]'); // To be developped
            $this->etlExecutionRepository->save($execution);
        }
    }

    protected function getFullExeptionTrace(\Throwable $exception)
    {
        $message = '';
        do {
            $message .= $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n\n";
        } while ($exception = $exception->getPrevious());

        return $message;
    }
}
