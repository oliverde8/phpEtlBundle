<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\MessageHandler;

use Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EtlExecutionHandler
{
    /** @var ChainProcessorsManager */
    protected $chainProcessorManager;

    /** @var EtlExecutionRepository */
    protected $etlExecutionRepository;

    /**
     * EtlExecutionHandler constructor.
     * @param ChainProcessorsManager $chainProcessorManager
     * @param EtlExecutionRepository $etlExecutionRepository
     */
    public function __construct(ChainProcessorsManager $chainProcessorManager, EtlExecutionRepository $etlExecutionRepository)
    {
        $this->chainProcessorManager = $chainProcessorManager;
        $this->etlExecutionRepository = $etlExecutionRepository;
    }

    public function __invoke(EtlExecutionMessage $message)
    {
        $execution = $this->etlExecutionRepository->find($message->getId());
        $this->chainProcessorManager->executeFromEtlEntity($execution);
    }
}
