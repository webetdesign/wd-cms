<?php

namespace WebEtDesign\CmsBundle\EventListener;

use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Services\PageProvider;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class SharedBlockAdminListener
{
    protected $provider;
    protected $em;
    protected $router;
    protected $fs;
    protected $kernel;
    protected $routeClass;

    public function __construct(PageProvider $provider, EntityManager $em, Router $router, Filesystem $fs, KernelInterface $kernel, $routeClass)
    {
        $this->provider   = $provider;
        $this->em         = $em;
        $this->router     = $router;
        $this->fs         = $fs;
        $this->kernel     = $kernel;
        $this->routeClass = $routeClass;
    }

    // create page form template configuration
    public function buildPage(PersistenceEvent $event)
    {
        $block = $event->getObject();

        if (!$block instanceof CmsSharedBlock) {
            return;
        }

        $config = $this->provider->getConfigurationFor($block->getTemplate());

        // hydrate content
        foreach ($config['contents'] as $content) {
            $CmsContent = new CmsContent();
            $CmsContent->setLabel($content['label']);
            $CmsContent->setType($content['type']);
            $block->addContent($CmsContent);
        }
    }
}
