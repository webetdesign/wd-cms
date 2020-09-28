<?php


namespace WebEtDesign\CmsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class CmsSitemapHelperCommand extends Command
{
    protected static $defaultName = 'cms:sitemap-helper';
    /**
     * @var CmsSiteRepository
     */
    private $cmsSiteRepository;
    private $cmsConfig;

    public function __construct(
        CmsSiteRepository $cmsSiteRepository,
        $cmsConfig,
        string $name = null
    ) {
        parent::__construct($name);
        $this->cmsSiteRepository = $cmsSiteRepository;
        $this->cmsConfig         = $cmsConfig;
    }


    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);

        if ($this->cmsConfig['multisite']) {
            $sites = $this->cmsSiteRepository->findAll();
            $io->block(count($sites) . ' sites trouvés :');

            $cmds = '';
            foreach ($sites as $site) {
                $host = $site->getHost();
                $dir  = 'public/sitemaps/' . $site->getSlug();
                $https = true;

                if (!file_exists($dir)) {
                    $fs = new Filesystem();
                    $fs->mkdir($dir);
                }


                $cmds .= "php bin/console cms:seo:sitemap $dir $host " . ($https ? '--scheme https' : '') . PHP_EOL;
            }

            $io->text($cmds);

        } else {
            $io->error('Cette command fonctionne que dans un context multisite');
        }

        return 0;
    }
}
