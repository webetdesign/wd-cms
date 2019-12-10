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
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsResetContentCommand extends Command
{
    protected static $defaultName = 'cms:reset-content';

    /**
     * @var SymfonyStyle
     */
    protected $io;
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var CmsPageRepository
     */
    protected $pageRp;
    /**
     * @var CmsSiteRepository
     */
    protected $siteRp;
    /**
     * @var CmsContentRepository
     */
    protected $contentRp;
    /**
     * @var TemplateProvider
     */
    protected $pageProvider;

    public function __construct(string $name = null, EntityManagerInterface $em, TemplateProvider $pageProvider)
    {
        $this->em           = $em;
        $this->pageProvider = $pageProvider;
        parent::__construct($name);
    }


    protected function configure()
    {
        $this
            ->setDescription('Reset content page with configuration')
            ->addArgument('pageId', InputArgument::OPTIONAL, 'page id')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Reset all page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pageRp    = $this->em->getRepository(CmsPage::class);
        $this->siteRp    = $this->em->getRepository(CmsSite::class);
        $this->contentRp = $this->em->getRepository(CmsContent::class);

        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('all')) {
            if ($this->io->confirm('Resetting all page, are you sure to continue')) {
                $pages = $this->pageRp->findAll();
                foreach ($pages as $page) {
                    $this->resetPage($page);
                }

                $this->io->success('Done');
                return 0;
            } else {
                return 0;
            }
        }

        $pageId = $input->getArgument('pageId');
        if (isset($pageId)) {
            $page = $this->pageRp->find($pageId);
        } else {
            $page = $this->selectPage();
        }

        if (!$page) {
            return 1;
        }

        $this->resetPage($page);

        $this->io->success('Done');

        return 0;
    }

    private function selectPage(): CmsPage
    {
        $sites = $this->em->getRepository(CmsSite::class)->findAll();
        if (count($sites) > 1) {
            $choices = [];
            foreach ($sites as $obj) {
                $choices[$obj->getId()] = $obj->getLabel() . ' (' . $obj->getId() . ')';
            }
            $reply = $this->io->choice('Site ', $choices);

            /** @var CmsSite $site */
            $site = $this->em->getRepository(CmsSite::class)->find(array_search($reply, $choices));
        } elseif (count($sites) === 0) {
            return null;
        } else {
            $site = $sites[0];
        }

        $pages = $site->getPages();

        $choices = [];
        foreach ($pages as $obj) {
            $choices[$obj->getId()] = $obj->getTitle() . ' (' . $obj->getId() . ')';
        }
        $reply = $this->io->choice('Page ', $choices);

        /** @var CmsPage $page */
        $page = $this->em->getRepository(CmsPage::class)->find(array_search($reply, $choices));
        return $page;
    }

    private function resetPage(?CmsPage $page)
    {
        $this->io->title('Reset page ' . $page->getTitle());

        try {
            $config = $this->pageProvider->getConfigurationFor($page->getTemplate());
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
            return 0;
        }

        $contentConf = [];
        foreach ($config['contents'] as $content) {
            $contentConf[$content['code']] = $content;
        }
        $codes = array_keys($contentConf);

        $ins  = $this->contentRp->findByParentInOutCodes($page, $codes, 'IN');
        $outs = $this->contentRp->findByParentInOutCodes($page, $codes, 'OUT');

        foreach ($outs as $out) {
            $this->em->remove($out);
        }

        $contentDone = [];

        /** @var CmsContent $in */
        foreach ($ins as $in) {
            $contentDone[] = $in->getCode();
            $conf          = $contentConf[$in->getCode()];
            $in->setPosition(array_search($in->getCode(), $codes));
            if (isset($conf['label'])) {
                $in->setLabel($conf['label']);
            }
            if (isset($conf['help'])) {
                $in->setHelp($conf['help']);
            }
            if ($in->getType() !== $conf['type']) {
                $in->setValue(null);
                $in->setMedia(null);
                $in->setSharedBlockList(null);
            }
            $this->em->persist($in);
        }

        foreach (array_diff($codes, $contentDone) as $code) {
            $conf    = $contentConf[$code];
            $content = new CmsContent();
            $content->setPosition(array_search($code, $codes));
            $content->setCode($code);
            $content->setType($conf['type']);
            $content->setPage($page);
            if (isset($conf['label'])) {
                $content->setLabel($conf['label']);
            }
            if (isset($conf['help'])) {
                $content->setHelp($conf['help']);
            }

            $this->em->persist($content);
        }

        $this->em->flush();
    }
}
