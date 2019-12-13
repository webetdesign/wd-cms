<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use Doctrine\ORM\EntityManager;

class PageDeclinationAdminListener
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        /** @var CmsPageDeclination $declination */
        $declination = $event->getObject();

        if (!$declination instanceof CmsPageDeclination) {
            return;
        }

        /** @var CmsContent $pageContent */
        foreach ($declination->getPage()->getContents() as $pageContent) {
            $content = new CmsContent();
            $content->setDeclination($declination);
            $content->setType($pageContent->getType());
            $content->setActive($pageContent->isActive());
            $content->setCode($pageContent->getCode());
            $content->setLabel($pageContent->getLabel());

            $declination->addContent($content);
        }
    }

}
