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
    public function __construct(
        protected readonly ChainProcessorsManager $chainProcessorManager,
        protected readonly EtlExecutionRepository $etlExecutionRepository
    ) {
    }

    public function __invoke(EtlExecutionMessage $message): void
    {
        $execution = $this->etlExecutionRepository->find($message->getId());
        $this->chainProcessorManager->executeFromEtlEntity($execution);
    }
}
