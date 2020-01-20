<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Doctrine\ORM\Event\;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
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

    public function prePersist($event)
    {
        $em = $event->getEntityManager();
        /** @var CmsSite $site */
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }

        if ($site->initPage) {
            $this->createPage($em, $site);
        }

        if ($site->initMenu) {
            $this->createMenu($em, $site);
        }
    }

    public function postUpdate($event)
    {
        $site = $event->getObject();

        if (!$site instanceof $this->siteClass) {
            return;
        }

        $this->warmUpRouteCache();
    }

    public function postPersist($event)
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
        $menu = new CmsMenu();
        $menu->setLabel('Menu principale')
            ->setCode('main_menu')
            ->setSite($site)
            ->setType(CmsMenuTypeEnum::DEFAULT);

        $site->addMenu($menu);

        $root = new CmsMenuItem();
        $root->setName('root ' . $site->getLabel() . ' ' . $menu->getLabel());
        $root->setRoot($root);
        $root->setMenu($menu);
        $em->persist($root);

        $homepage = new CmsMenuItem();
        $homepage->setName("Homepage");
        $homepage->setParent($root);
        $homepage->setMenu($menu);
        $em->persist($homepage);
    }

    private function createPage(EntityManager $em, CmsSite $site)
    {
        $page = new CmsPage();
        $page->setTemplate(!empty($site->getTemplateFilter()) ? $site->getTemplateFilter() . '_home' : 'home');
        $page->setTitle('Homepage');
        $page->rootPage = true;
        $site->addPage($page);

        $em->persist($page);
    }
}
