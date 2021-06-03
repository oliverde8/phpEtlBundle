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

    public function getCountInStatus(\DateTime $startTime, \DateTime $endTime, string $status): int
    {
        $result = $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id) as count')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->andWhere("cm.status = :status")->setParameter('status', $status)
            ->getQuery()
            ->getSingleResult();

        return (isset($result['count'])) ? $result['count'] : 0;
    }

    public function getMostExecutedJobs(\DateTime $startTime, \DateTime $endTime, int $maxResults): array
    {
        return $this->createQueryBuilder('cm')
            ->select('cm.name as name, COUNT(cm.id) as count')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->groupBy("cm.name")
            ->orderBy("count", 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getArrayResult();
    }

    public function getMostTimeSpentJobs(\DateTime $startTime, \DateTime $endTime, int $maxResults): array
    {
        return $this->createQueryBuilder('cm')
            ->select('cm.name as name, SUM(cm.runTime) as runTime')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->groupBy("cm.name")
            ->orderBy("runTime", 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getArrayResult();
    }

    public function getLongestJobs(\DateTime $startTime, \DateTime $endTime, int $maxResults): array
    {
        return $this->createQueryBuilder('cm')
            ->select('cm.name as name, MAX(cm.runTime) as runTime')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->groupBy("cm.name")
            ->orderBy("runTime", 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getArrayResult();
    }

    public function getMaxWaitTime(\DateTime $startTime, \DateTime $endTime): int
    {
        $result = $this->createQueryBuilder('cm')
            ->select('MAX(cm.runTime) as waitTime')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->getQuery()
            ->getSingleResult();

        return (isset($result['waitTime'])) ? $result['waitTime'] : 0;
    }

    public function getAvgWaitTime(\DateTime $startTime, \DateTime $endTime): int
    {
        $result = $this->createQueryBuilder('cm')
            ->select('AVG(cm.runTime) as waitTime')
            ->where("cm.createTime > :startTime")->setParameter('startTime', $startTime)
            ->andWhere("cm.createTime <= :endTime")->setParameter('endTime', $endTime)
            ->getQuery()
            ->getSingleResult();

        return (isset($result['waitTime'])) ? $result['waitTime'] : 0;
    }
}
