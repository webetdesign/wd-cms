<?php

namespace WebEtDesign\CmsBundle\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use LogicException;

/**
 * @method CmsMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsMenu[]    findAll()
 * @method CmsMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsMenuRepository extends NestedTreeRepository
{

    public function __construct(RegistryInterface $registry) {
        $manager = $registry->getManagerForClass(CmsMenu::class);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                CmsMenu::class
            ));
        }

        parent::__construct($manager, $manager->getClassMetadata(CmsMenu::class));
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
            ->setParameter('code', $code)
//            ->setMaxResults(1)
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
            ->setParameter('root', $root)
//            ->setMaxResults(1)
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

    public function findRoot(){
        $qb = $this->createQueryBuilder('m')
            ->where('m.code LIKE :code')
            ->setParameter('code', '%root%');
        return $qb->getQuery()->getResult();
    }

    public function flatNodes(CmsMenu $menu)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->where('n.lft > :left')
            ->addSelect('p')
            ->addSelect('r')
            ->addSelect('c')
            ->addSelect('s2')
            ->addSelect('s3')
            ->leftJoin('n.page', 'p')
            ->leftJoin('p.route', 'r')
            ->leftJoin('n.children', 'c')
            ->leftJoin('p.site', 's2')
            ->leftJoin('n.site', 's3')
            ->andHaving('n.rgt < :right')
            ->andWhere('n.root = :root')
            ->setParameter('left', $menu->getLft())
            ->setParameter('right', $menu->getRgt())
            ->setParameter('root', $menu->getRoot())
            ->orderBy('n.lft', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }
}
