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
    /** @var ContainerInterface */
    protected $container;

    /** @var EtlExecutionRepository */
    protected $etlExecutionRepository;

    /** @var ChainExecutionLogger */
    protected $chainExecutionLogger;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $definitions;


    /**
     * ChainProcessorsManager constructor.
     * @param ContainerInterface $container
     * @param EtlExecutionRepository $etlExecutionRepository
     * @param array $definitions
     */
    public function __construct(
        ContainerInterface $container,
        EtlExecutionRepository $etlExecutionRepository,
        ChainExecutionLogger $chainExecutionLogger,
        LoggerInterface $logger,
        array $definitions
    ) {
        $this->container = $container;
        $this->etlExecutionRepository = $etlExecutionRepository;
        $this->chainExecutionLogger = $chainExecutionLogger;
        $this->logger = $logger;
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
     * @param $iterator
     * @param array $params
     *
     * @throws \Exception
     */
    public function execute(string $chainName, $iterator, array $params)
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
     * @param EtlExecution $execution
     * @param null $iterator
     * @throws ChainOperationException
     */
    public function executeFromEtlEntity(EtlExecution $execution, $iterator = null)
    {
        $this->chainExecutionLogger->setCurrentExecution($execution);

        $chainName = $execution->getName();
        $processor = $this->getProcessor($chainName);
        $params = json_decode($execution->getInputOptions());


        if (is_null($iterator)) {
            $iterator = new \ArrayIterator(json_decode($execution->getInputData()));
        }

        $execution->setStatus(EtlExecution::STATUS_RUNNING);
        $execution->setStartTime(new \DateTime());
        $this->etlExecutionRepository->save($execution);
        $params['etl'] = ['chain' => $chainName, 'startTime' => new \DateTime()];

        try {
            $this->logger->info("Starting etl process!", $params);
            $processor->process($iterator, $params);
            $execution->setStatus(EtlExecution::STATUS_SUCCESS);

            $this->logger->info("Finished etl process!", $params);
        } catch (\Exception $exception) {
            $params['exception'] = $exception;
            $this->logger->info("Failed during etl process!", $params);

            $execution->setFailTime(new \DateTime());
            $execution->setStatus(EtlExecution::STATUS_FAILURE);
            $execution->setErrorMessage($exception->getMessage() . "\n" . $exception->getTraceAsString());
            throw $exception;
        } finally {
            $execution->setEndTime(new \DateTime());
            $execution->setStepStats('[]'); // To be developped
            $this->etlExecutionRepository->save($execution);

            $this->chainExecutionLogger->setCurrentExecution(null);
        }
    }
}
