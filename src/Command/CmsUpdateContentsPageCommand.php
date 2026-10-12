<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;

#[AsCommand(
    name: 'cms:page:update-contents',
    description: 'Update configuration of content\'s pages and declination with configuration file',
)]
class CmsUpdateContentsPageCommand extends AbstractCmsUpdateContentsCommand
{
    protected CmsPageRepository $pageRp;
    private TemplateRegistry         $templateRegistry;

    public function __construct(
        EntityManagerInterface $em,
        TemplateRegistry $templateRegistry,
        ?string $name = null
    ) {
        parent::__construct($em, $name);
        $this->templateRegistry = $templateRegistry;
    }


    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(
            description: 'template name',
        )]
        ?string $template = null,
        #[Option(
            shortcut: 'a',
            description: 'Reset all page',
        )]
        bool $all = false,
        #[Option(
            shortcut: 'p',
            description: 'Page id',
        )]
        ?int $page = null
    ): int
    {
        $this->init($input, $output);
        $this->pageRp = $this->em->getRepository(CmsPage::class);

        if ($all) {
            if ($this->io->confirm('Resetting all page\' configuration, are you sure to continue')) {
                $templates = array_values($this->templateRegistry->getChoiceList(TemplateRegistry::TYPE_PAGE));
                foreach ($templates as $template) {
                    $this->processTemplate($template);
                }
                $this->io->success('Done');
            }
            return Command::SUCCESS;
        }

        $pageId = $page;
        if (isset($pageId)) {
            $page = $this->pageRp->find($pageId);
            if ($page) {
                $this->resetPage($page);
                $this->io->success('Done');
                return Command::SUCCESS;
            }
        }

        if (!$template) {
            $template = $this->selectTemplate();
        }

        $this->processTemplate($template);

        $this->io->success('Done');
        return Command::SUCCESS;
    }

    public function processTemplate($template): void
    {
        $pages = $this->pageRp->findByTemplate($template);

        foreach ($pages as $page) {
            $this->resetPage($page);
        }
    }

    protected function resetPage(?CmsPage $page): bool
    {
        $this->io->title('Update page ' . $page->getTitle());

        try {
            $config = $this->templateRegistry->get($page->getTemplate());
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

    protected function selectTemplate(): string
    {
        $templates = $this->templateRegistry->getChoiceList(TemplateRegistry::TYPE_PAGE);

        return $this->io->choice('Template', array_flip($templates));
    }
}
