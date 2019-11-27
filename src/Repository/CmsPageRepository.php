<?php

namespace WebEtDesign\CmsBundle\Repository;

use Doctrine\Common\Collections\Collection;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use LogicException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;

/**
 * @method CmsPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsPage[]    findAll()
 * @method CmsPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsPageRepository extends NestedTreeRepository
{

    public function __construct(RegistryInterface $registry)
    {
        $manager = $registry->getManagerForClass(CmsPage::class);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                CmsPage::class
            ));
        }

        parent::__construct($manager, $manager->getClassMetadata(CmsPage::class));
    }


    /**
     * @param $name
     * @return CmsPage[]|Collection
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByRouteName($name)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->leftJoin('p.route', 'r')
            ->leftJoin('p.site', 's')
            ->leftJoin('p.declinations', 'd')
            ->addSelect('r', 's', 'd')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1);


        return $qb->getQuery()->getOneOrNullResult();

    }

    public function getPagesBySite(CmsSite $site)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.site = :site')
            ->setParameter('site', $site)
            ->orderBy('p.lft', 'ASC');

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
