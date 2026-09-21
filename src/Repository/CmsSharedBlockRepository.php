<?php

namespace WebEtDesign\CmsBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CmsSharedBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsSharedBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsSharedBlock[]    findAll()
 * @method CmsSharedBlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsSharedBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    public function findByTemplate($template)
    {
        $qb = $this->createQueryBuilder('sb');
        $qb->where('sb.template = :template')
            ->setParameter('template', $template);

        return $qb->getQuery()->getResult();
    }

    public function getBuilderByCollections(?array $collections = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('sb');

        if (!empty($collections)) {
            $qb->join('sb.site', 's');
            $qb->andWhere($qb->expr()->in('s.templateFilter', ':templateFilter'))
                ->setParameter('templateFilter', $collections);
        }

        return $qb;
    }
}
