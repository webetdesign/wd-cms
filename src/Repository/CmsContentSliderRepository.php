<?php

namespace WebEtDesign\CmsBundle\Repository;

use WebEtDesign\CmsBundle\Entity\CmsContentSlider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CmsContentSlider|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsContentSlider|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsContentSlider[]    findAll()
 * @method CmsContentSlider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsContentSliderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CmsContentSlider::class);
    }

    // /**
    //  * @return CmsContentSlider[] Returns an array of CmsContentSlider objects
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
    public function findOneBySomeField($value): ?CmsContentSlider
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
