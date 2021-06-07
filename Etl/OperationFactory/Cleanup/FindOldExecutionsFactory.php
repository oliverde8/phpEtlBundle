<?php

namespace Oliverde8\PhpEtlBundle\Etl\OperationFactory\Cleanup;

use Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainOperationInterface;
use Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup\FindOldExecutionsOperation;
use Oliverde8\PhpEtlBundle\Repository\EtlExecutionRepository;
use Symfony\Component\Validator\Constraints as Assert;

class FindOldExecutionsFactory extends AbstractFactory
{
    protected EtlExecutionRepository $etlExecutionRepository;

    protected string $minKeep;

    /**
     * FindOldExecutionsFactory constructor.
     * @param EtlExecutionRepository $etlExecutionRepository
     * @param string $minKeep
     */
    public function __construct(EtlExecutionRepository $etlExecutionRepository, string $minKeep)
    {
        $this->etlExecutionRepository = $etlExecutionRepository;
        $this->minKeep = $minKeep;

        $this->operation = 'Etl/Cleanup/FindOldExecutions';
        $this->class = FindOldExecutionsOperation::class;
    }


    /**
     * Build an operation of a certain type with the options.
     *
     * @param String $operation
     * @param array $options
     *
     * @return ChainOperationInterface
     */
    protected function build($operation, $options)
    {
        return $this->create($this->etlExecutionRepository, $this->getKeepDate($options));
    }


    /**
     * Configure validation.
     *
     * @return Constraint
     */
    protected function configureValidator()
    {
        return new Assert\Collection([
            'keep' => new Assert\Type(["type" => "string"])
        ]);
    }

    protected function getKeepDate(array $options): \DateTime
    {
        $keepDate = $options['keep'] ?? $this->minKeep;

        $wantedKeepDate = strtotime("- " . $keepDate);
        $minKeepDate = strtotime("- " . $this->minKeep);

        if ($wantedKeepDate > $minKeepDate) {
            throw new \Exception("You can't delete orders from $keepDate ago, the minimum keep is " . $this->minKeep);
        }

        return (new \DateTime())->setTimestamp($wantedKeepDate);
    }
}
