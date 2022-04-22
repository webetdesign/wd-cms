<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebEtDesign\CmsBundle\CmsTemplate\PageInterface;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteAttributeDefinition;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsRouteInterface;
use WebEtDesign\CmsBundle\Factory\TemplateFactoryInterface;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;

class CmsUpdatePageParamsCommand extends AbstractCmsUpdateContentsCommand
{
    protected static $defaultName = 'cms:page:update-params';

    protected CmsPageRepository $pageRp;

    protected ?array                   $configCms;
    protected TemplateFactoryInterface $pageFactory;

    public function __construct(
        EntityManagerInterface $em,
        TemplateFactoryInterface $pageFactory,
        array $configCms,
        string $name = null
    ) {
        parent::__construct($em, $name);
        $this->configCms   = $configCms;
        $this->pageFactory = $pageFactory;
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
                $templates = array_values($this->pageFactory->getTemplateList());

                foreach ($templates as $template) {
                    $this->processTemplate($template);
                }
                $this->io->success('Done');
            }
            return 0;
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

    protected function resetPage(?CmsPage $page): void
    {
        $this->io->title('Update page ' . $page->getTitle());

        try {
            $config = $this->pageFactory->get($page->getTemplate());
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
        }

        if (isset($config) && $config instanceof PageInterface && $page->getRoute() !== null) {
            $this->updateRouteMetadata($page, $config);

            $this->updateParams($page->getRoute(), $config);
        }

    }

    private function updateRouteMetadata(CmsPage $page, PageInterface $config): void
    {
        $route = $page->getRoute();

        $routeConfig = $config->getRoute();

        if (!empty($routeConfig->getController())) {
            $controller = $routeConfig->getController();
            $controller .= '::' . (!empty($routeConfig->getAction()) ? $routeConfig->getAction() : '__invoke');
            $route->setController($controller);
        }

        $route->setMethods($routeConfig->getMethods());

        $defaultName = $routeConfig->getName();
        if ($this->configCms['multilingual']) {
            $routeName = $defaultName ? sprintf('%s_%s', $page->getSite()->getLocale(),
                $defaultName) : sprintf('%s_cms_route_%s', $page->getSite()->getLocale(),
                $page->getId());
        } else {
            $routeName = $defaultName ?: sprintf('cms_route_%s', $page->getId());
        }

        // Pour éviter le problème de doublon de route
        $exists = $this->em->getRepository(CmsRoute::class)->findSameRoute($route, $routeName);

        if (is_array($exists) && count($exists) > 0) {
            $routeName .= '_' . uniqid();
        }

        $route->setName($routeName);
    }

    private function updateParams(CmsRouteInterface $cmsRoute, PageInterface $config)
    {
        $routeConfig = $config->getRoute();

        preg_match('/{.*}/', $cmsRoute->getPath(), $defined);

        $attributes = [];

        foreach ($routeConfig->getAttributes() as $attribute) {
            $attributes[$attribute->getName()] = $attribute;
        }

        foreach ($defined as $item) {
            $param = str_replace(['{', '}'], '', $item);
            if (!array_key_exists($param, $attributes)) {
                $cmsRoute = $this->removeParam($cmsRoute, $param);
            }
        }

        foreach ($attributes as $name => $attribute) {
            if (strpos($cmsRoute->getPath(), $name) < 0 || !strpos($cmsRoute->getPath(), $name)) {
                $cmsRoute = $this->addParam($cmsRoute, $attribute);
            }
            $this->upsertDefaultAndRequirement($cmsRoute, $attribute);
        }

        $cmsRoute->setPath(
            str_replace('//', '/', $cmsRoute->getPath())
        );

        $this->em->persist($cmsRoute);
    }

    private function removeParam(CmsRouteInterface $cmsRoute, $param): CmsRouteInterface
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

    private function addParam(CmsRouteInterface $cmsRoute, RouteAttributeDefinition $attribute): CmsRouteInterface
    {
        $cmsRoute->setPath($cmsRoute->getPath() . '/{' . $attribute->getName() . '}');

        return $cmsRoute;
    }

    private function upsertDefaultAndRequirement(CmsRouteInterface $cmsRoute, RouteAttributeDefinition $attribute): void
    {
        if (!empty($attribute->getDefault())) {
            $defaults         = json_decode($cmsRoute->getDefaults(), true);
            $defaults[$attribute->getName()] = $attribute->getDefault();
            $cmsRoute->setDefaults(json_encode($defaults));
        }

        if (!empty($attribute->getRequirement())) {
            $requirements         = json_decode($cmsRoute->getRequirements(), true);
            $requirements[$attribute->getName()] = $attribute->getRequirement();
            $cmsRoute->setRequirements(json_encode($requirements));
        }

    }

    protected function selectTemplate(): string
    {
        $templates = $this->pageFactory->getTemplateChoices();

        return $this->io->choice('Template', array_flip($templates));
    }
}
