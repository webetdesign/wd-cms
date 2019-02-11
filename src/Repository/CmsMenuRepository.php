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

    public function getRootByName($rootName)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.name = :name')
            ->andWhere('m.lvl = 0')
            ->setParameter('name', $rootName);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
