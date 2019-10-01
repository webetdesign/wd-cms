<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 27/09/2019
 * Time: 15:02
 */

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsSite;

class CmsSiteListener implements EventSubscriber
{

    public function getSubscribedEvents()
    {
        return [
            'postPersist'
        ];
    }

    /**
     * @param LifecycleEventArgs $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof CmsSite) {
            return;
        }

        $site = $entity;

        $em = $event->getEntityManager();

        /**
         * @var CmsMenu[] $menus
         */
        $menus = $em->getRepository(CmsMenu::class)->findRoot();

        // Aucun menu existant
        if (empty($menus)){
            $this->createMenu($em, $site);

            return;

        }

        /**
         * @var CmsSite[] $sites
         */
        $sites = $em->getRepository(CmsSite::class)->findAll();

        foreach ($menus as $key => $menu) {
            foreach ($sites as $site) {
                if ($site->getMenu() && $site->getMenu()->getId() == $menu->getId()){
                    unset($menus[$key]);
                }
            }
        }

        // aucun menu non associé existant
        if (empty($menus)){
            $this->createMenu($em, $site);
            return;
        }


        // menu existant non associé
        $site->setMenu($menus[array_key_first($menus)]);
        $this->updateMenu($em, $menus[array_key_first($menus)], $site);



    }

    /**
     * @param EntityManager $em
     * @param CmsSite $site
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createMenu(EntityManager $em, CmsSite $site){
        $root = new CmsMenu();
        $root->setCode("root_" . $site->getSlug());
        $root->setName('root');

        $em->persist($root);

        $root->setRoot($root);

        $main_menu = new CmsMenu();
        $main_menu->setCode("main_menu_" . $site->getSlug());
        $main_menu->setname("Main menu");
        $main_menu->setParent($root);

        $em->persist($main_menu);

        $homepage = new CmsMenu();
        $homepage->setCode("homepage_" . $site->getSlug());
        $homepage->setName("Homepage");
        $homepage->setParent($main_menu);

        $em->persist($homepage);

        $site->setMenu($root);
        $em->flush();


    }

    /**
     * @param EntityManager $em
     * @param CmsMenu $menu
     * @param CmsSite $site
     * @throws \Doctrine\ORM\ORMException
     */
    private function updateMenu(EntityManager $em, CmsMenu $menu, CmsSite $site){
        $menu->setCode(
            $menu->getSlug() . "_" . $site->getSlug()
        );

        $em->persist($menu);

        foreach ($menu->getChildren() as $child) {
            $child->setCode(
                $child->getSlug() . "_" . $site->getSlug()
            );
            $em->persist($child);
        }

        $em->flush();
    }
}
