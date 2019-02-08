<?php

namespace WebEtDesign\CmsBundle\Repository;

use WebEtDesign\CmsBundle\Entity\CmsMenu;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @method CmsMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsMenu[]    findAll()
 * @method CmsMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsMenuRepository extends NestedTreeRepository
{
    public function getRootByName($rootName)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.name = :name')
            ->andWhere('m.lvl = 0')
            ->setParameter('name', $rootName);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
