<?php


namespace WebEtDesign\CmsBundle\EventListener;


use Doctrine\Persistence\Event\LifecycleEventArgs;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;

class MenuAdminListener
{
    public function postPersist(LifecycleEventArgs $event): void
    {
        $menu = $event->getObject();

        if (!$menu instanceof CmsMenu) {
            return;
        }

        if ($menu->initRoot) {
            $em = $event->getObjectManager();

            $root = new CmsMenuItem();
            $root->setName('root ' . $menu->getSite() . ' ' . $menu->getLabel());
            $root->setMenu($menu);

            $em->persist($root);
            $em->flush();
        }
    }

}
