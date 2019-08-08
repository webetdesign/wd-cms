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

    public function findParent(CmsContent $content)
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->select('m.id AS mid, IDENTITY(m.parent) AS parent_id, m.lvl, m.lft, p.id AS pid, c.id AS cid, c.type, c.code, c.parent_heritance')
            ->leftJoin('c.page', 'p')
            ->leftJoin(CmsMenu::class, 'm', 'WITH', 'm.page = p')
            ->where('c.code LIKE :code')
            ->andWhere('c.type LIKE :type')
            ->orderBy('m.lft', 'ASC')
            ->setParameter('type', $content->getType())
            ->setParameter('code', $content->getCode());

        $list = $qb->getQuery()->getResult();

        $output_id = $this->search($list, $content->getPage()->getId());

        if ($output_id) {
            return $this->createQueryBuilder('c')
                ->where('c.id = :id')
                ->setParameter('id', $output_id)
                ->getQuery()
                ->getOneOrNullResult();
        } else {
            return $content;
        }
    }

    private function search($list, $page_id)
    {
        // parent courant
        $parent = $list[array_search($page_id, array_column($list, 'pid'))]['parent_id'];

        $exist = array_search($parent, array_column($list, 'mid'));

        if ($exist) {
            $block = $list[$exist];
            if ($block['parent_heritance'] == false) {
                return $block['cid'];
            } else {
                return $this->search($list, $block['pid']);
            }
        } else {
            foreach ($list as $block) {
                if ($block['parent_heritance'] == false) {
                    return $block['cid'];
                }
            }
        }

        return false;
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
