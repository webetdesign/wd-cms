<?php

namespace WebEtDesign\CmsBundle\Command;

use Sonata\Exporter\Handler;
use Sonata\Exporter\Writer\SitemapWriter;
use Sonata\SeoBundle\Sitemap\SourceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\CmsBundle\Utils\SitemapIterator;

class CmsSitemapGeneratorCommand extends Command implements ContainerAwareInterface
{
    protected static $defaultName = 'cms:seo:sitemap';

    private RouterInterface $router;

    private SourceManager $sitemapManager;

    private Filesystem $filesystem;

    private SitemapIterator $smithSitemapIterator;

    /**
     * @deprecated since sonata-project/seo-bundle 2.0
     *
     * @var ContainerInterface|null
     */
    private $container;
    private $configCms;

    public function __construct(
        RouterInterface $router,
        SourceManager $sitemapManager,
        Filesystem $filesystem,
        SitemapIterator $smithSitemapIterator,
        $configCms
    ) {
        $this->router               = $router;
        $this->sitemapManager       = $sitemapManager;
        $this->filesystem           = $filesystem;
        $this->smithSitemapIterator = $smithSitemapIterator;
        $this->configCms            = $configCms;

        parent::__construct();
    }

    /**
     * @deprecated since sonata-project/seo-bundle 2.0
     *
     * NEXT_MAJOR Remove deprecated methods, remove interface implementation, cleanup 'use' block.
     * NEXT_MAJOR Make arguments of __construct required instead of optional.
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        @trigger_error('Injection of container has been deprecated. Consider injection of each service you need in your console command declaration.',
            E_USER_DEPRECATED);

        $this->container = $container;

        if (null === $container) {
            return;
        }

        $this->router         = $container->get('router');
        $this->sitemapManager = $container->get('sonata.seo.sitemap.manager');
        $this->filesystem     = $container->get('filesystem');
    }

    /**
     * @deprecated since sonata-project/seo-bundle 2.0
     */
    public function getContainer(): ?ContainerInterface
    {
        @trigger_error('Please, avoid injection of container in your services.', E_USER_DEPRECATED);

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->addArgument('dir', InputArgument::REQUIRED,
            'The directory to store the sitemap.xml file');
        $this->addArgument('host', InputArgument::REQUIRED, 'Set the host');
        $this->addOption('scheme', null, InputOption::VALUE_OPTIONAL, 'Set the scheme', 'http');
        $this->addOption('baseurl', null, InputOption::VALUE_OPTIONAL, 'Set the base url', '');
        $this->addOption(
            'sitemap_path',
            null,
            InputOption::VALUE_OPTIONAL,
            'Set the sitemap relative path (if in a specific directory)',
            ''
        );

        $this->setDescription('Create a sitemap');
        $this->setHelp(<<<'EOT'
The <info>cms:seo:sitemap</info> command create new sitemap files (index + sitemap).

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $host         = $input->getArgument('host');
        $scheme       = $input->getOption('scheme');
        $baseUrl      = $input->getOption('baseurl');
        $permanentDir = $input->getArgument('dir');
        $appendPath   = $input->hasOption('sitemap_path') ? $input->getOption('sitemap_path') : $baseUrl;

        $this->getContext()->setHost($host);
        $this->getContext()->setScheme($scheme);
        $this->getContext()->setBaseUrl($baseUrl);

        $tempDir = $this->createTempDir($output);
        if (null === $tempDir) {
            $output->writeln('<error>The temporary directory already exists</error>');
            $output->writeln('<error>If the task is not running please delete this directory</error>');

            return 1;
        }

        $output->writeln(sprintf('Generating sitemap - this can take a while'));
        $this->generateSitemap($tempDir, $scheme, $host, $appendPath);

        $output->writeln(sprintf('Moving temporary file to %s ...', $permanentDir));
        $this->moveTemporaryFile($tempDir, $permanentDir);

        $output->writeln('Cleanup ...');
        $this->filesystem->remove($tempDir);

        $output->writeln('<info>done!</info>');

        return 0;
    }

    private function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    /**
     * Creates temporary directory if one does not exist.
     *
     * @return string|null Directory name or null if directory is already exist
     */
    private function createTempDir(OutputInterface $output): ?string
    {
        $tempDir = sys_get_temp_dir() . '/sonata_sitemap_' . md5(__DIR__);

        $output->writeln(sprintf('Creating temporary directory: %s', $tempDir));

        if ($this->filesystem->exists($tempDir)) {
            return null;
        }

        $this->filesystem->mkdir($tempDir);

        return $tempDir;
    }

    /**
     * @throws \Exception
     */
    private function generateSitemap(
        string $dir,
        string $scheme,
        string $host,
        string $appendPath
    ): void {
        // generate cms sitemap
        preg_match('/^(www\.)?(.+)\..+/', $host, $match);
        $hostname = $match[2];

        // multisite
        foreach ($this->sitemapManager as $group => $sitemap) {

            if ($this->configCms['multisite'] && strpos($group, $hostname) === false) {
                continue; // keep group prefixed with correct hostname
            }

            $write = new SitemapWriter($dir, $group, $sitemap->types, false);
            try {
                Handler::create($sitemap->sources, $write)->export();
            } catch (\Exception $e) {
                $this->filesystem->remove($dir);

                throw $e;
            }
        }

        $this->smithSitemapIterator->configure($this->configCms['multisite'] ? $host : null);
        $write = new SitemapWriter($dir, 'pages', [], false);
        try {
            Handler::create($this->smithSitemapIterator, $write)->export();
        } catch (\Exception $e) {
            $this->filesystem->remove($dir);

            throw $e;
        }

        // generate global sitemap index
        SitemapWriter::generateSitemapIndex(
            $dir,
            sprintf('%s://%s%s', $scheme, $host, $appendPath),
            'sitemap*.xml',
            'sitemap.xml'
        );
    }

    private function moveTemporaryFile(string $tempDir, string $permanentDir): void
    {
        $oldFiles = Finder::create()->files()->name('sitemap*.xml')->in($permanentDir);
        foreach ($oldFiles as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        $newFiles = Finder::create()->files()->name('sitemap*.xml')->in($tempDir);
        foreach ($newFiles as $file) {
            $this->filesystem->rename($file->getRealPath(),
                sprintf('%s/%s', $permanentDir, $file->getFilename()));
        }
    }
}
