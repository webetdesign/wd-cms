<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsUpdateContentsPageCommand extends AbstractCmsUpdateContentsCommand
{
    protected static $defaultName = 'cms:page:update-contents';

    /**
     * @var CmsPageRepository
     */
    protected $pageRp;


    public function __construct(string $name = null, EntityManagerInterface $em, TemplateProvider $pageProvider)
    {
        parent::__construct($name, $em, $pageProvider);
    }


    protected function configure()
    {
        $this
            ->setDescription('Update configuration of content\'s pages and declination with configuration file')
            ->addArgument('template', InputArgument::OPTIONAL, 'template name')
            ->addOption('all', '-a', InputOption::VALUE_NONE, 'Reset all page')
            ->addOption('page', '-p', InputOption::VALUE_REQUIRED, 'Page id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, $output);
        $this->pageRp    = $this->em->getRepository(CmsPage::class);

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

        $this->processContent($page, $config);

        if (count($page->getDeclinations()) > 0) {
            /** @var CmsPageDeclination $declination */
            foreach ($page->getDeclinations() as $declination) {
                $this->io->title('Reset declination ' . $declination->getTitle());
                $this->processContent($declination, $config);
            }
        }

        return true;
    }
}
