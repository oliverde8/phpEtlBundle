<?php

namespace Oliverde8\PhpEtlBundle\Entity;

use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtlExecutionRepository::class)
 *
 */
class EtlExecution
{
    public const STATUS_WAITING ="waiting";
    public const STATUS_QUEUED ="queued";
    public const STATUS_RUNNING = "running";
    public const STATUS_SUCCESS = "success";
    public const STATUS_FAILURE = "failure";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $inputData;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $inputOptions;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startTime;
    /**
     * @ORM\Column(type="integer")
     */
    private $waitTime = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $runTime = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $failTime;

    /**
     * @ORM\Column(type="text")
     */
    private $definition;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $errorMessage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $stepStats;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * EtlExecution constructor.
     * @param string $name
     * @param string $definition
     */
    public function __construct(string $name, string $definition, array $inputData, array $inputOptions)
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->createTime = new \DateTime();
        $this->status = self::STATUS_WAITING;

        $this->inputData = json_encode($inputData);
        $this->inputOptions = json_encode($inputOptions);
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * @param mixed $inputData
     */
    public function setInputData($inputData): void
    {
        $this->inputData = $inputData;
    }

    /**
     * @return mixed
     */
    public function getInputOptions()
    {
        return $this->inputOptions;
    }

    /**
     * @param mixed $inputOptions
     */
    public function setInputOptions($inputOptions): void
    {
        $this->inputOptions = $inputOptions;
    }

    public function getCreateTime(): \DateTime
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTime $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getWaitTime(): int
    {
        return $this->waitTime;
    }

    /**
     * @param int $waitTime
     */
    public function setWaitTime(int $waitTime): void
    {
        $this->waitTime = $waitTime;
    }

    /**
     * @return mixed
     */
    public function getFailTime()
    {
        return $this->failTime;
    }

    /**
     * @param mixed $failTime
     */
    public function setFailTime($failTime): void
    {
        $this->failTime = $failTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getRunTime(): int
    {
        return $this->runTime;
    }

    /**
     * @param int $runTime
     */
    public function setRunTime(int $runTime): void
    {
        $this->runTime = $runTime;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param mixed $errorMessage
     */
    public function setErrorMessage($errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getStepStats(): ?string
    {
        if ($this->stepStats == "[]") {
            // Legacy json stuff.
            return null;
        }
        return $this->stepStats;
    }

    public function setStepStats(?string $stepStats): self
    {
        $this->stepStats = $stepStats;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
