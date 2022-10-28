<?php

namespace Insitaction\DoctrineLoggerBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class DoctrineLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $action;

    /**
     * @ORM\Column(type="string")
     */
    private string $entity;

    /**
     * @ORM\Column(type="integer")
     */
    private int $entityId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $fieldName = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $lastFieldValue = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $newFieldValue = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $date;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $userEntity = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?int $userId = null;

    public function __construct()
    {
        $this->date = new DateTime('NOW');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): self
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function getLastFieldValue(): ?string
    {
        return $this->lastFieldValue;
    }

    public function setLastFieldValue(?string $lastFieldValue): self
    {
        $this->lastFieldValue = $lastFieldValue;

        return $this;
    }

    public function getNewFieldValue(): ?string
    {
        return $this->newFieldValue;
    }

    public function setNewFieldValue(?string $newFieldValue): self
    {
        $this->newFieldValue = $newFieldValue;

        return $this;
    }

    public function getUserEntity(): ?string
    {
        return $this->userEntity;
    }

    public function setUserEntity(?string $userEntity): self
    {
        $this->userEntity = $userEntity;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
