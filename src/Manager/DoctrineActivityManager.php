<?php

namespace Insitaction\DoctrineLoggerBundle\Manager;

use DateTimeInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Cache\Exception\FeatureNotImplemented;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Exception;
use Insitaction\DoctrineLoggerBundle\Annotation\Loggeable;
use Insitaction\DoctrineLoggerBundle\Entity\DoctrineLog;
use ReflectionClass;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class DoctrineActivityManager
{
    private Security $security;
    private Reader $annotationReader;
    private PropertyAccessor $pac;
    private EntityManagerInterface $em;
    private string $action;
    private ?UserInterface $user = null;
    private object $entity;
    private ?int $entityId = null;

    public function __construct(
        Security $security,
        Reader $annotationReader,
        EntityManagerInterface $em
    ) {
        $this->security = $security;
        $this->em = $em;
        $this->annotationReader = $annotationReader;
        $this->pac = PropertyAccess::createPropertyAccessor();
    }

    private function logUpdateActivity(): void
    {
        $entityChangeset = $this->em->getUnitOfWork()->getEntityChangeSet($this->entity);

        foreach ($entityChangeset as $fieldName => $changeSet) {
            $doctrineLog = $this->initDoctrineLog($fieldName);
            $this->addOldAndNewValue(
                $fieldName,
                $doctrineLog,
                $changeSet[0],
                $changeSet[1]
            );

            $this->em->persist($doctrineLog);
        }

        $this->em->flush();
    }

    private function logRemoveActivity(): void
    {
        $this->em->persist($this->initDoctrineLog());
        $this->em->flush();
    }

    private function logCreateActivity(): void
    {
        $metadata = $this->em->getClassMetadata(get_class($this->entity));

        foreach ($metadata->fieldMappings as $fieldName => $changeSet) {
            $doctrineLog = $this->initDoctrineLog($fieldName);
            $this->addOldAndNewValue(
                $fieldName,
                $doctrineLog,
                $metadata->getFieldValue($this->entity, $fieldName)
            );

            $this->em->persist($doctrineLog);
        }

        foreach ($metadata->associationMappings as $fieldName => $changeSet) {
            $doctrineLog = $this->initDoctrineLog($fieldName);

            try {
                $doctrineLog->setFieldName($metadata->getSingleAssociationJoinColumnName($fieldName));
            } catch (Exception $e) {
                $doctrineLog->setFieldName($metadata->getAssociationMapping($fieldName)['fieldName']);
            }

            $this->setRelationFieldValues($doctrineLog, $fieldName);
            $this->em->persist($doctrineLog);
        }
        $this->em->flush();
    }

    private function dateTimeToString(?DateTimeInterface $dateTime): ?string
    {
        if (null === $dateTime) {
            return null;
        }

        return $dateTime->format(DateTimeInterface::W3C);
    }

    /**
     * @param array<mixed, mixed>|null $array
     */
    private function arrayToString(?array $array): ?string
    {
        if (null === $array) {
            return null;
        }

        return json_encode($array, JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed|null $string
     */
    private function mixedToStringOrnull($string): ?string
    {
        if (null === $string) {
            return null;
        }

        return (string)$string;
    }

    /**
     * @param mixed $newFieldValue
     * @param mixed $lastFieldValue
     */
    private function addOldAndNewValue(string $fieldName, DoctrineLog $doctrineLog, $newFieldValue, $lastFieldValue = null): void
    {
        $fieldMapping = $this->em->getClassMetadata(get_class($this->entity))->getFieldMapping($fieldName);

        switch ($fieldMapping['type']) {
            case Types::BINARY:
                $doctrineLog->setLastFieldValue('anon.');
                $doctrineLog->setNewFieldValue('anon.');
                break;
            case Types::DATE_IMMUTABLE:
            case Types::DATE_MUTABLE:
            case Types::DATEINTERVAL:
            case Types::DATETIME_IMMUTABLE:
            case Types::DATETIME_MUTABLE:
            case Types::DATETIMETZ_IMMUTABLE:
            case Types::DATETIMETZ_MUTABLE:
                $doctrineLog->setLastFieldValue($this->dateTimeToString($lastFieldValue));
                $doctrineLog->setNewFieldValue($this->dateTimeToString($newFieldValue));
                break;
            case Types::ARRAY:
                $doctrineLog->setLastFieldValue($this->arrayToString($lastFieldValue));
                $doctrineLog->setNewFieldValue($this->arrayToString($newFieldValue));
            // no break
            default:
                $doctrineLog->setLastFieldValue($this->mixedToStringOrnull($lastFieldValue));
                $doctrineLog->setNewFieldValue($this->mixedToStringOrnull($newFieldValue));
        }
    }

    public function logActivity(string $action, object $entity): void
    {
        $annot = $this->annotationReader->getClassAnnotation(new ReflectionClass($entity), Loggeable::class);

        if (!$annot instanceof Loggeable) {
            return;
        }

        $this->entity = $entity;
        $this->action = $action;
        $this->loadUser();

        if (method_exists($this->entity, 'getId')) {
            $this->entityId = $this->entity->getId();
        }

        switch ($action) {
            case Events::postPersist:
                $this->logCreateActivity();
                break;
            case Events::preRemove:
                $this->logRemoveActivity();
                break;
            case Events::postUpdate:
                $this->logUpdateActivity();
                break;
            default:
                throw new FeatureNotImplemented('Event not implemented');
        }
    }

    private function loadUser(): void
    {
        $token = $this->security->getToken();

        if (null !== $token) {
            $this->user = $token->getUser();
        }
    }

    private function initDoctrineLog(?string $fieldName = null): DoctrineLog
    {
        $doctrineLog = new DoctrineLog();
        $doctrineLog->setAction($this->action);
        $doctrineLog->setEntity(get_class($this->entity));
        $doctrineLog->setEntityId($this->entityId);
        $doctrineLog->setFieldName($fieldName);

        if (null !== $this->user) {
            $doctrineLog->setUserEntity(get_class($this->user));
            if (method_exists($this->user, 'getId')) {
                $doctrineLog->setUserId($this->user->getId());
            }
        }

        return $doctrineLog;
    }

    private function setRelationFieldValues(DoctrineLog $doctrineLog, string $fieldName): void
    {
        $relations = $this->pac->getValue($this->entity, $fieldName);

        if (null !== $relations) {
            if ($relations instanceof Collection && 0 !== $relations->count()) {
                $ids = '';
                foreach ($relations as $relation) {
                    if ($this->relationHasId($relation)) {
                        $ids .= $relation->getId() . ' ';
                    }
                }

                $doctrineLog->setNewFieldValue($ids);
            } else {
                if ($this->relationHasId($relations)) {
                    $doctrineLog->setNewFieldValue((string)$relations->getId());
                }
            }
        }
    }

    private function relationHasId(object $relation): bool
    {
        return method_exists($relation, 'getId') && null !== $relation->getId();
    }
}
