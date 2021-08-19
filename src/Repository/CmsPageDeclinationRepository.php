<?php

namespace WebEtDesign\CmsBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method CmsPageDeclination|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsPageDeclination|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsPageDeclination[]    findAll()
 * @method CmsPageDeclination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsPageDeclinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CmsPageDeclination::class);
    }

    // /**
    //  * @return CmsPageDeclination[] Returns an array of CmsPageDeclination objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CmsPageDeclination
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
