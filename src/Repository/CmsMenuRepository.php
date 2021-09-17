<?php

namespace WebEtDesign\CmsBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WebEtDesign\CmsBundle\Entity\CmsMenu;

/**
 * @method CmsMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsMenu[]    findAll()
 * @method CmsMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CmsMenu::class);
    }

    // /**
    //  * @return CmsMenu[] Returns an array of CmsMenu objects
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
    public function findOneBySomeField($value): ?CmsMenu
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
