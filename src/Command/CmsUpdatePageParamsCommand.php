<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsRouteInterface;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsUpdatePageParamsCommand extends AbstractCmsUpdateContentsCommand
{
    protected static $defaultName = 'cms:page:update-params';

    protected CmsPageRepository $pageRp;

    protected ?array $configCms;

    public function __construct(
        string $name = null,
        EntityManagerInterface $em,
        TemplateProvider $pageProvider,
        array $configCms
    ) {
        parent::__construct($name, $em, $pageProvider);
        $this->configCms = $configCms;
    }


    protected function configure()
    {
        $this
            ->setDescription('Update pages parameters and declination with configuration file')
            ->addArgument('template', InputArgument::OPTIONAL, 'template name')
            ->addOption('all', '-a', InputOption::VALUE_NONE, 'Reset all page')
            ->addOption('page', '-p', InputOption::VALUE_REQUIRED, 'Page id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, $output);
        $this->pageRp = $this->em->getRepository(CmsPage::class);

        if ($input->getOption('all')) {
            if ($this->io->confirm('Resetting all page\' configuration, are you sure to continue')) {
                $templates = array_values($this->templateProvider->getTemplateList());

                foreach ($templates as $template) {
                    $this->processTemplate($template);
                }
                $this->io->success('Done');
                return 0;
            } else {
                return 0;
            }
        }

        $pageId = $input->getOption('page');
        if (isset($pageId)) {
            $page = $this->pageRp->find($pageId);
            if ($page) {
                $this->resetPage($page);
                $this->io->success('Done');
                return 0;
            }
        }

        $template = $input->getArgument('template');
        if (!$template) {
            $template = $this->selectTemplate();
        }

        $this->processTemplate($template);

        $this->io->success('Done');
        return 0;
    }

    public function processTemplate($template)
    {
        $pages = $this->pageRp->findByTemplate($template);

        foreach ($pages as $page) {
            $this->resetPage($page);
        }
        $this->em->flush();
    }

    protected function resetPage(?CmsPage $page)
    {
        $this->io->title('Update page ' . $page->getTitle());

        try {
            $config = $this->templateProvider->getConfigurationFor($page->getTemplate());
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
            return false;
        }

        if (!$page->getRoute()) {
            return false;
        }

        $this->updateParams($page->getRoute(), $config);

        $this->updateRouteMetadata($page, $config);

        return true;
    }

    private function updateRouteMetadata(CmsPage $page, $config)
    {
        $route = $page->getRoute();

        if(!empty($config['controller']) && strlen($config['controller']) > 0 && !empty($config['action']) & strlen($config['action']) > 0){
            $route->setController($config['controller'] . '::' . $config['action']);

        }
        if(!empty($config['methods']) && count($config['methods']) > 0){
            $route->setMethods($config['methods']);
        }

        if (!empty($config['route'])) {
            $defaultName = $config['route'];
            if ($this->configCms['multilingual']) {
                $routeName = $defaultName ? sprintf('%s_%s', $page->getSite()->getLocale(), $defaultName) : sprintf('%s_cms_route_%s', $page->getSite()->getLocale(), $page->getId());
            } else {
                $routeName = $defaultName ? sprintf('%s', $defaultName) : sprintf('cms_route_%s', $page->getId());
            }

            // Pour éviter le problème de doublon de route
            $exists = $this->em->getRepository(CmsRoute::class)->findSameRoute($route, $routeName);

            if (is_array($exists) && count($exists) > 0) {
                $routeName .= '_' . uniqid();
            }

            $route->setName($routeName);
        }

        return $route;
    }

    private function updateParams(CmsRouteInterface $cmsRoute, $config)
    {
        preg_match('/{.*}/', $cmsRoute->getPath(), $defined);
        $config = isset($config['params']) ? $config['params'] : [];

        foreach ($defined as $item) {
            $param = str_replace(['{', '}'], '', $item);
            if (!array_key_exists($param, $config)) {
                $cmsRoute = $this->removeParam($cmsRoute, $param);
            }
        }

        foreach ($config as $param => $item) {
            if (strpos($cmsRoute->getPath(), $param) < 0 || !strpos($cmsRoute->getPath(), $param)) {
                $cmsRoute = $this->addParam($cmsRoute, $param);
            }
            $this->upsertDefaultAndRequirement($cmsRoute, $param, $config[$param]);
        }

        $cmsRoute->setPath(
            str_replace('//', '/', $cmsRoute->getPath())
        );

        $this->em->persist($cmsRoute);
    }

    private function removeParam(CmsRouteInterface $cmsRoute, $param)
    {
        $cmsRoute->setPath(
            str_replace('/{' . $param . '}', '', $cmsRoute->getPath())
        );

        $defaults = json_decode($cmsRoute->getDefaults(), true);
        if (isset($defaults[$param])) {
            unset($defaults[$param]);
        }
        $cmsRoute->setDefaults(json_encode($defaults));
        $requirements = json_decode($cmsRoute->getRequirements(), true);
        if (isset($requirements[$param])) {
            unset($requirements[$param]);
        }
        $cmsRoute->setRequirements(json_encode($requirements));
        return $cmsRoute;
    }

    private function addParam(CmsRouteInterface $cmsRoute, $param)
    {
        $cmsRoute->setPath($cmsRoute->getPath() . '/{' . $param . '}');

        return $cmsRoute;
    }

    private function upsertDefaultAndRequirement(CmsRouteInterface $cmsRoute, $param, $config)
    {
        if (isset($config['default'])) {
            $defaults         = json_decode($cmsRoute->getDefaults(), true);
            $defaults[$param] = $config['default'];
            $cmsRoute->setDefaults(json_encode($defaults));
        }

        if (isset($config['requirement'])) {
            $requirements         = json_decode($cmsRoute->getRequirements(), true);
            $requirements[$param] = $config['requirement'];
            $cmsRoute->setRequirements(json_encode($requirements));
        }

        return $cmsRoute;
    }
}
