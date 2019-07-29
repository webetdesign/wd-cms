<?php

namespace WebEtDesign\CmsBundle\Repository;

use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CmsSharedBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsSharedBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsSharedBlock[]    findAll()
 * @method CmsSharedBlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsSharedBlockRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CmsSharedBlock::class);
    }

    public function findDuplicate($code)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.template = :code')
            ->setParameter('code', $code);

        $result = $qb->getQuery()->getResult();

        return sizeof($result);
    }

    // /**
    //  * @return CmsSharedBlock[] Returns an array of CmsSharedBlock objects
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
    public function findOneBySomeField($value): ?CmsSharedBlock
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
