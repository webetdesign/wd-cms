<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository;

#[AsCommand(
    name: 'cms:shared-block:update-contents',
    description: 'Update configuration of content\'s sharedBlock with configuration file',
)]
class CmsUpdateContentsSharedBlockCommand extends AbstractCmsUpdateContentsCommand
{
    /**
     * @var CmsSharedBlockRepository
     */
    protected CmsSharedBlockRepository $sharedBlockRp;
    protected TemplateRegistry         $templateRegistry;

    public function __construct(
        EntityManagerInterface $em,
        TemplateRegistry $templateRegistry,
        string $name = null,
    ) {
        parent::__construct($em, $name);
        $this->templateRegistry = $templateRegistry;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template', InputArgument::OPTIONAL, 'template name')
            ->addOption('all', '-a', InputOption::VALUE_NONE, 'Reset all page')
            ->addOption('block', '-b', InputOption::VALUE_REQUIRED, 'sharedBlock id');
    }


    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, $output);
        $this->sharedBlockRp = $this->em->getRepository(CmsSharedBlock::class);

        if ($input->getOption('all')) {
            if ($this->io->confirm('Resetting all page\' configuration, are you sure to continue')) {
                $templates = array_values($this->templateRegistry->getTemplateList());
                foreach ($templates as $template) {
                    $this->processTemplate($template->getCode());
                }
                $this->io->success('Done');
                return Command::SUCCESS;
            } else {
                return Command::SUCCESS;
            }
        }

        $blockId = $input->getOption('block');
        if (isset($blockId)) {
            $block = $this->sharedBlockRp->find($blockId);
            if ($block) {
                $this->resetSharedBlock($block);
                $this->io->success('Done');
                return Command::SUCCESS;
            }
        }

        $template = $input->getArgument('template');
        if (!$template) {
            $template = $this->selectTemplate();
        }

        $this->processTemplate($template);

        $this->io->success('Done');
        return Command::SUCCESS;
    }

    public function processTemplate($template): void
    {
        $pages = $this->sharedBlockRp->findByTemplate($template);

        foreach ($pages as $page) {
            $this->resetSharedBlock($page);
        }
    }

    private function resetSharedBlock(?CmsSharedBlock $block): void
    {
        $this->io->title('Update sharedBlock ' . $block->getLabel());

        try {
            $config = $this->templateRegistry->get($block->getTemplate());
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
            return;
        }

        $this->processContent($block, $config);
    }

    protected function selectTemplate(): string
    {
        $templates = $this->templateRegistry->getTemplateChoices();

        return $this->io->choice('Template', array_flip($templates));
    }
}
