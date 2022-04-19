<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use Doctrine\ORM\EntityManager;

class PageDeclinationAdminListener
{
    protected $em;
    protected $pageConfig;
    private   $cmsConfig;

    public function __construct(EntityManager $em, $pageConfig, $cmsConfig)
    {
        $this->em = $em;
        $this->pageConfig = $pageConfig;
        $this->cmsConfig = $cmsConfig;

    }

    public function prePersist($event)
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

        $technicName = $this->generateTechnicName($declination);
        $declination
            ->setTechnicName($technicName)
            ->setLocale($declination->getPage()->getSite()->getLocale())
        ;
    }

    public function preUpdate($event)
    {
        /** @var CmsPageDeclination $declination */
        $declination = $event->getObject();

        if (!$declination instanceof CmsPageDeclination) {
            return;
        }

        $technicName = $this->generateTechnicName($declination);

        $declination
            ->setTechnicName($technicName)
            ->setLocale($declination->getPage()->getSite()->getLocale());
    }

    private function generateTechnicName(CmsPageDeclination $declination) {

        $technicName = $declination->getPage()->getRoute()->getName();
        $values = json_decode($declination->getParams(), true);
        $route = $declination->getPage()->getRoute();
        $config = $this->pageConfig[$declination->getPage()->getTemplate()];

        foreach ($values as $name => $value) {
            $param = $config['params'][$name] ?? null;
            if ($param && isset($param['entity']) && isset($param['property'])) {
                if ($this->cmsConfig['multilingual'] && is_subclass_of($param['entity'], TranslatableInterface::class)) {
                    $method = 'findOneBy' . ucfirst($param['property']);
                    $locale = $declination->getPage()->getSite()->getLocale();
                    $entity = $this->em->getRepository($param['entity'])->$method($value, $locale);
                } else {
                    $entity = $this->em->getRepository($param['entity'])->findOneBy([$param['property'] => $value]);
                }

                if ($entity) {
                    $technicName .= '__' . $name . '_' . $entity->getId();
                }
                $values[$name] = $entity ?? null;
            }
        }

        return $technicName;
    }
}
