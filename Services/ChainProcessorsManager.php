<?php


namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\Item\DataItem;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Psr\Container\ContainerInterface;

class ChainProcessorsManager
{
    /** @var ContainerInterface */
    protected $container;

    /** @var EtlExecutionRepository */
    protected $etlExecutionRepository;

    /** @var array */
    protected $definitions;

    /**
     * ChainProcessorsManager constructor.
     * @param ContainerInterface $container
     * @param EtlExecutionRepository $etlExecutionRepository
     * @param array $definitions
     */
    public function __construct(ContainerInterface $container, EtlExecutionRepository $etlExecutionRepository, array $definitions)
    {
        $this->container = $container;
        $this->etlExecutionRepository = $etlExecutionRepository;
        $this->definitions = $definitions;
    }

    public function getDefinition(string $chainName): string
    {
        return $this->definitions[$chainName];
    }

    public function getProcessor(string $chainName): ChainProcessor
    {
        return $this->container->get("oliverde8.etl.chain.$chainName");
    }

    public function execute(string $chainName, $iterator, array $params)
    {
        $definition = $this->getDefinition($chainName);
        $processor = $this->getProcessor($chainName);

        $inputData = ["Iterator! Can't show input data"];
        if (is_array($iterator)) {
            $inputData = $iterator;
            $iterator = new \ArrayIterator($iterator);
        }

        $execution = new EtlExecution($chainName, $definition, $inputData, $params);
        $execution->setStatus(EtlExecution::STATUS_RUNNING);
        $this->etlExecutionRepository->save($execution);

        $params['etl'] = ['chain' => $chainName, 'startTime' => new \DateTime()];

        try {
            $processor->process($iterator, $params);
            $execution->setStatus(EtlExecution::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $execution->setFailTime(new \DateTime());
            $execution->setStatus(EtlExecution::STATUS_FAILURE);
            $execution->setErrorMessage($exception->getMessage() . "\n" . $exception->getTraceAsString());
            throw $exception;
        } finally {
            $execution->setEndTime(new \DateTime());
            $execution->setStepStats('[]'); // To be developped
            $this->etlExecutionRepository->save($execution);
        }
    }
}
