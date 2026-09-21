<?php
/**
 * Created by PhpStorm.
 * User: Leo MEYER
 * Date: 07/08/2019
 * Time: 16:12
 */
namespace WebEtDesign\CmsBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WebEtDesign\CmsBundle\Entity\CmsSite;

/**
 * @method CmsSite|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsSite|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsSite[]    findAll()
 * @method CmsSite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsSiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CmsSite::class);
    }

    public function getDefault()
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.default = true');

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findOther($site, $templateFilter = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s <> :site')
            ->setParameter('site', $site);

        if ($templateFilter) {
            $qb->andWhere('s.templateFilter = :templateFilter')
                ->setParameter('templateFilter', $templateFilter);
        }

        return $qb->getQuery()->getResult();
    }

    public function findSitesMenu()
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.menu > 0');
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return CmsPage[] Returns an array of CmsPage objects
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
    public function findOneBySomeField($value): ?CmsPage
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
