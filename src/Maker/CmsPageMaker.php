<?php

namespace WebEtDesign\CmsBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteDefinition;

class CmsPageMaker extends AbstractMaker
{

    public function __construct() { }

    const SKELETON_DIR = __DIR__ . '/../Resources/skeleton';
    protected string $shortName = '';
    protected array  $config = [
        'useStatements' => []
    ];

    public static function getCommandName(): string
    {
        return 'make:cms:page';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new cms page configuration class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('class', InputArgument::OPTIONAL,
                sprintf('Choose a name for your page configuration class (e.g. <fg=yellow>%sPage</>)',
                    Str::asClassName(Str::getRandomTerm())));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('class'),
            'CMS\\Page\\',
            'Page'
        );

        $this->shortName = Str::asFilePath($classNameDetails->getRelativeNameWithoutSuffix());

        $code                 = strtoupper($this->shortName);
        $this->config['code'] = $io->ask('Code', $code);

        $this->config['label'] = $io->ask('Label');

        $templateName                 = $this->shortName . '.html.twig';
        $this->config['templatePath'] = $io->ask('Template', 'pages/' . $templateName);

        $templateParent = $io->ask('Template parent', 'layout/default_layout.html.twig');

        $this->configureRoute($io, $generator);

        $generator->generateClass($classNameDetails->getFullName(),
            sprintf('%s/page.tpl.php', self::SKELETON_DIR),
            [
                'config'            => $this->config,
                'parent_class_name' => 'AbstractPage',
            ]
        );

        $generator->generateTemplate(
            $this->config['templatePath'],
            sprintf('%s/page_twig.tpl.php', self::SKELETON_DIR),
            [
                'template_parent' => $templateParent
            ]
        );

        $generator->writeChanges();
    }


    public function configureRoute(ConsoleStyle $io, Generator $generator)
    {
        if (!$io->confirm('Add a RouteDefinition')) {
            return;
        }

        $this->addUseStatement(RouteDefinition::class);

        $this->config['route'] = [
            'path'       => $io->ask(sprintf('Route path (e.g. <fg=yellow>/%s</>)', $this->shortName)),
            'name'       => $io->ask(sprintf('Route name (e.g. <fg=yellow>%s</>)', $this->shortName)),
            'controller' => $io->ask(sprintf('Controller (e.g. <fg=yellow>App\Controller\%sController::__invoke</>)', Str::asCamelCase($this->shortName))),
        ];
    }

    public function addUseStatement($value)
    {
        if (!in_array($value, $this->config['useStatements'])) {
            $this->config['useStatements'][] = $value;
        }

        sort($this->config['useStatements']);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
