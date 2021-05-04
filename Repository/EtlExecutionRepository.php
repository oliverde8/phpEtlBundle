<?php

namespace Oliverde8\PhpEtlBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;

/**
 * @method EtlExecution|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtlExecution|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtlExecution[]    findAll()
 * @method EtlExecution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtlExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtlExecution::class);
    }

    public function save(EtlExecution $execution)
    {
        $this->_em->persist($execution);
        $this->_em->flush();
    }
}
