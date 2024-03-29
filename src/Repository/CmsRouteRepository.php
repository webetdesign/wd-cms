<?php

namespace WebEtDesign\CmsBundle\Repository;

use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CmsRoute|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsRoute|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsRoute[]    findAll()
 * @method CmsRoute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CmsRoute::class);
    }

    public function findSameRoute(CmsRoute $route, $routeName){
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')
            ->andWhere('c.id != :id')
            ->setParameters([
                'name' => $routeName,
                'id' => $route->getId()
            ])
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return CmsRoute[] Returns an array of CmsRoute objects
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
    public function findOneBySomeField($value): ?CmsRoute
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
