<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class PageDeclinationAdminListener
{
    protected                $em;
    protected                $pageConfig;
    private                  $cmsConfig;
    private TemplateRegistry $templateRegistry;

    public function __construct(EntityManagerInterface $em, TemplateRegistry $templateRegistry, ParameterBagInterface $parameterBag)
    {
        $this->em               = $em;
        $this->cmsConfig        = $parameterBag->get('wd_cms.cms');
        $this->templateRegistry = $templateRegistry;
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
            ->setLocale($declination->getPage()->getSite()->getLocale());
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

    private function generateTechnicName(CmsPageDeclination $declination)
    {

        $technicName = $declination->getPage()->getRoute()->getName();
        $values      = json_decode($declination->getParams(), true);
        /** @var PageInterface $config */
        $config = $this->templateRegistry->get($declination->getPage()->getTemplate());

        foreach ($values as $name => $value) {
            $attribute = $config->getRoute()->getAttribute($name);
            if ($attribute && !empty($attribute->getEntityClass())) {
                if ($this->cmsConfig['multilingual'] && is_subclass_of($attribute->getEntityClass(), TranslatableInterface::class)) {
                    $method = 'findOneBy' . ucfirst(!empty($attribute->getEntityProperty()) ? $attribute->getEntityProperty() : 'id');
                    $locale = $declination->getPage()->getSite()->getLocale();
                    $entity = $this->em->getRepository($attribute->getEntityClass())->$method($value, $locale);
                } else {
                    $entity = $this->em->getRepository($attribute->getEntityClass())->findOneBy(['id' => $value]);
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
