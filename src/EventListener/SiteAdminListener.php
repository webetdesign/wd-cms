<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class SiteAdminListener
{
    protected                     $router;
    protected                     $fs;
    protected                     $kernel;
    protected                     $siteClass;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        Router $router,
        Filesystem $fs,
        KernelInterface $kernel,
        $siteClass,
        ParameterBagInterface $parameterBag
    ) {
        $this->router       = $router;
        $this->fs           = $fs;
        $this->kernel       = $kernel;
        $this->siteClass    = $siteClass;
        $this->parameterBag = $parameterBag;
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

//        foreach (['matcher_cache_class', 'generator_cache_class'] as $option) {
//            $className = $this->router->getOption($option);
//            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';
//            $this->fs->remove($cacheFile);
//        }

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
        $menu->setLabel('Menu principal')
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
        $tmplName = $this->parameterBag->get('wd_cms.cms')['default_home_template'];

        $page = new CmsPage();
        $page->setTemplate(!empty($site->getTemplateFilter()) ? ($site->getTemplateFilter() . '_' . $tmplName) : $tmplName);
        $page->setTitle('Homepage');
        $page->rootPage = true;
        $site->addPage($page);

        $em->persist($page);
    }
}
