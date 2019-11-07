<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class SiteAdminListener
{
    protected $router;
    protected $fs;
    protected $kernel;
    protected $siteClass;

    public function __construct(Router $router, Filesystem $fs, KernelInterface $kernel, $siteClass)
    {
        $this->router    = $router;
        $this->fs        = $fs;
        $this->kernel    = $kernel;
        $this->siteClass = $siteClass;
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        /** @var CmsSite $site */
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }

        $this->createMenu($em, $site);

        $this->createPage($em, $site);
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }


        $this->warmUpRouteCache();
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }

        $this->warmUpRouteCache();
    }

    // remove cache routing file and warmup cache
    protected function warmUpRouteCache()
    {
        $cacheDir = $this->kernel->getCacheDir();

        foreach (['matcher_cache_class', 'generator_cache_class'] as $option) {
            $className = $this->router->getOption($option);
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';
            $this->fs->remove($cacheFile);
        }

        $this->router->warmUp($cacheDir);
    }

    /**
     * @param EntityManager $em
     * @param CmsSite $site
     * @throws \Doctrine\ORM\ORMException
     */
    private function createMenu(EntityManager $em, CmsSite $site)
    {
        $root = new CmsMenu();
        $root->setCode("root_" . $site->getSlug());
        $root->setName('root');

        $em->persist($root);

        $root->setRoot($root);

        $main_menu = new CmsMenu();
        $main_menu->setCode("main_menu");
        $main_menu->setname("Main menu");
        $main_menu->setParent($root);

        $em->persist($main_menu);

        $homepage = new CmsMenu();
        $homepage->setName("Homepage");
        $homepage->setParent($main_menu);

        $em->persist($homepage);

        $site->setMenu($root);

    }

    private function createPage(EntityManager $em, CmsSite $site)
    {
        $page = new CmsPage();
        $page->setTemplate(!empty($site->getTemplateFilter()) ? $site->getTemplateFilter() . '_home' : 'home');
        $page->setTitle('Homepage');
        $page->rootPage = true;

        $em->persist($page);
        $site->setPage($page);
    }
}
