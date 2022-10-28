<?php

namespace Insitaction\DoctrineLoggerBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Insitaction\DoctrineLoggerBundle\Manager\DoctrineActivityManager;

class DoctrineActivitySubscriber implements EventSubscriberInterface
{
    private DoctrineActivityManager $doctrineActivityManager;

    public function __construct(DoctrineActivityManager $doctrineActivityManager)
    {
        $this->doctrineActivityManager = $doctrineActivityManager;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->doctrineActivityManager->logActivity(Events::postPersist, $args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->doctrineActivityManager->logActivity(Events::preRemove, $args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->doctrineActivityManager->logActivity(Events::postUpdate, $args->getObject());
    }
}
