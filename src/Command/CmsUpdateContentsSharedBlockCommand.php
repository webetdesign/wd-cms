<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsUpdateContentsSharedBlockCommand extends AbstractCmsUpdateContentsCommand
{
    protected static $defaultName = 'cms:shared-block:update-contents';

    /**
     * @var CmsSharedBlockRepository
     */
    protected $sharedBlockRp;

    public function __construct(string $name = null, EntityManagerInterface $em, TemplateProvider $blockProvider)
    {
        parent::__construct($name, $em, $blockProvider);
    }


    protected function configure()
    {
        $this
            ->setDescription('Update configuration of content\'s sharedBlock with configuration file')
            ->addArgument('template', InputArgument::OPTIONAL, 'template name')
            ->addOption('all', '-a', InputOption::VALUE_NONE, 'Reset all page')
            ->addOption('block', '-b', InputOption::VALUE_REQUIRED, 'sharedBlock id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, $output);
        $this->sharedBlockRp = $this->em->getRepository(CmsSharedBlock::class);

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

        $blockId = $input->getOption('block');
        if (isset($blockId)) {
            $block = $this->sharedBlockRp->find($blockId);
            if ($block) {
                $this->resetSharedBlock($block);
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
        $pages = $this->sharedBlockRp->findByTemplate($template);

        foreach ($pages as $page) {
            $this->resetSharedBlock($page);
        }
    }

    private function resetSharedBlock(?CmsSharedBlock $block)
    {
        $this->io->title('Update sharedBlock ' . $block->getLabel());

        try {
            $config = $this->templateProvider->getConfigurationFor($block->getTemplate());
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
            return false;
        }

        $this->processContent($block, $config);

        return true;
    }
}
