<?php

namespace WebEtDesign\CmsBundle\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use LogicException;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;

/**
 * @method CmsMenuItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsMenuItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsMenuItem[]    findAll()
 * @method CmsMenuItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsMenuItemRepository extends NestedTreeRepository
{

    public function __construct(RegistryInterface $registry)
    {
        $manager = $registry->getManagerForClass(CmsMenuItem::class);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                CmsMenuItem::class
            ));
        }

        parent::__construct($manager, $manager->getClassMetadata(CmsMenuItem::class));
    }

    public function getByName($name)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.name = :name')
            ->setParameter('name', $name);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getRootByName($rootName)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.name = :name')
            ->andWhere('m.lvl = 0')
            ->setParameter('name', $rootName);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getByCode($code)
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.site', 's')
            ->addSelect('s')
            ->where('m.code = :code')
            ->setParameter('code', $code)//            ->setMaxResults(1)
        ;
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getByCodeAndRoot($code, $root)
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.children', 'c')
            ->addSelect('c')
            ->where('m.code = :code')
            ->andWhere('m.root = :root')
            ->setParameter('code', $code)
            ->setParameter('root', $root)//            ->setMaxResults(1)
        ;
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getRootByCode($rootCode)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.code = :code')
            ->andWhere('m.lvl = 0')
            ->setParameter('code', $rootCode);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findRoot()
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.code LIKE :code')
            ->setParameter('code', '%root%');
        return $qb->getQuery()->getResult();
    }

    public function flatNodes(CmsMenu $menu)
    {
        $qb = $this->createQueryBuilder('mi');
        $qb
            ->select(['mi', 'p', 'r', 'c', 's'])
            ->leftJoin('mi.page', 'p')
            ->leftJoin('p.route', 'r')
            ->leftJoin('mi.children', 'c')
            ->leftJoin('p.site', 's')
            ->where('mi.menu = :menu')
            ->setParameter('menu', $menu)
            ->orderBy('mi.lft', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getPageArboMenuItem(CmsPage $page)
    {
        $site = $page->getSite();

        $qb = $this->createQueryBuilder('mi');
        $qb
            ->join('mi.menu', 'm')
            ->where('mi.page = :page')
            ->andWhere('m.site = :site')
            ->andWhere('m.type = :type')
            ->setParameter('page', $page)
            ->setParameter('site', $site)
            ->setParameter('type', CmsMenuTypeEnum::PAGE_ARBO);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
