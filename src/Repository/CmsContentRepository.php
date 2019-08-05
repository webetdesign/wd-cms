<?php

namespace WebEtDesign\CmsBundle\Repository;

use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;

/**
 * @method CmsContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsContent[]    findAll()
 * @method CmsContent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsContentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CmsContent::class);
    }

    public function findOneByObjectAndContentCodeAndType($object, $contentCode, $type = [])
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.code = :code');

        if ($object instanceof CmsPage) {
            $qb->andWhere('c.page = :page')
                ->setParameter('page', $object);
        }
        if ($object instanceof CmsSharedBlock) {
            $qb->andWhere('c.sharedBlockParent = :sharedBlock')
                ->setParameter('sharedBlock', $object);
        }

        $qb->setParameter('code', $contentCode);

        if (sizeof($type) > 0) {
            $qb->andWhere($qb->expr()->in('c.type', ':type'))
                ->setParameter('type', $type);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCode(CmsPage $page, $contentCode)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.code LIKE :code')
            ->andWhere('c.page = :page')
            ->setParameter('page', $page)
            ->setParameter('code', $contentCode);

        return $qb->getQuery()
            ->getResult();
    }



    // /**
    //  * @return CmsContent[] Returns an array of CmsContent objects
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
    public function findOneBySomeField($value): ?CmsContent
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
