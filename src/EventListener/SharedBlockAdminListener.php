<?php

namespace WebEtDesign\CmsBundle\EventListener;

use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Event\PersistenceEvent;

class SharedBlockAdminListener
{
    protected SharedBlockFactory $sharedBlockFactory;
    protected EntityManager      $em;

    public function __construct(
        SharedBlockFactory $sharedBlockFactory,
        EntityManager $em,
    ) {
        $this->sharedBlockFactory = $sharedBlockFactory;
        $this->em                 = $em;
    }

    // create page form template configuration
    public function buildSharedBlock(PersistenceEvent $event)
    {
        $block = $event->getObject();

        if (!$block instanceof CmsSharedBlock) {
            return;
        }

        $config = $this->sharedBlockFactory->get($block->getTemplate());

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
