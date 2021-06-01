<?php

namespace Oliverde8\PhpEtlBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EtlExecutionEventSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ChainProcessorsManager */
    protected $chainProcessorManager;

    /** @var MessageBusInterface */
    protected $messageBus;

    /**
     * EtlExecutionEventSubscriber constructor.
     * @param EntityManagerInterface $em
     * @param ChainProcessorsManager $chainProcessorManager
     * @param MessageBusInterface $messageBus
     */
    public function __construct(EntityManagerInterface $em, ChainProcessorsManager $chainProcessorManager, MessageBusInterface $messageBus)
    {
        $this->em = $em;
        $this->chainProcessorManager = $chainProcessorManager;
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['setChainDetails'],
            AfterEntityPersistedEvent::class => ['queueChainExecution'],
        ];
    }

    public function setChainDetails(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof EtlExecution) {
            return;
        }

        $definition = $this->chainProcessorManager->getDefinition($entity->getName());
        $entity->setDefinition($definition);
        $entity->setStatus(EtlExecution::STATUS_WAITING);
    }

    public function queueChainExecution(AfterEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof EtlExecution) {
            return;
        }

        try {
            $this->messageBus->dispatch(new EtlExecutionMessage($entity->getId()));
        } catch (\Exception $e) {
            $this->em->remove($entity);
            $this->em->flush();

            throw $e;
        }
    }
}
