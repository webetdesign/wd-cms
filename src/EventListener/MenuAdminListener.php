<?php


namespace WebEtDesign\CmsBundle\EventListener;


use Doctrine\ORM\Event\LifecycleEventArgs;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;

class MenuAdminListener
{
    public function postPersist($event)
    {
        $menu = $event->getEntity();

        if (!$menu instanceof CmsMenu) {
            return;
        }

        if ($menu->initRoot) {
            $em = $event->getEntityManager();

            $root = new CmsMenuItem();
            $root->setName('root ' . $menu->getSite() . ' ' . $menu->getLabel());
            $root->setMenu($menu);

            $em->persist($root);
            $em->flush();
        }
    }

}
