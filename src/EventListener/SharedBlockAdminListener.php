<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use Sonata\AdminBundle\Event\PersistenceEvent;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class SharedBlockAdminListener
{
    protected TemplateRegistry $templateRegistry;
    protected EntityManagerInterface      $em;

    public function __construct(
        TemplateRegistry $templateRegistry,
        EntityManagerInterface $em,
    ) {
        $this->templateRegistry = $templateRegistry;
        $this->em               = $em;
    }

    // create page form template configuration
    public function buildSharedBlock(PersistenceEvent $event)
    {
        $block = $event->getObject();

        if (!$block instanceof CmsSharedBlock) {
            return;
        }

        $config = $this->templateRegistry->get($block->getTemplate());

        $duplicate = $this->em->getRepository(CmsSharedBlock::class)
            ->findDuplicate($block->getTemplate());

        $block->setCode($block->getTemplate() . ($duplicate > 0 ? '_' . $duplicate : ''));

        // hydrate content
        foreach ($config->getBlocks() as $object) {
            $CmsContent = new CmsContent();
            $CmsContent->setCode($object->getCode());
            $CmsContent->setLabel($object->getLabel());
            $CmsContent->setType($object->getType());
            $block->addContent($CmsContent);
        }
    }
}
