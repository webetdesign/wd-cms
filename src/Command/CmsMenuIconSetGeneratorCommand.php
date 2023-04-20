<?php
declare(strict_types=1);


namespace WebEtDesign\CmsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CmsMenuIconSetGeneratorCommand extends Command
{
    private array    $iconSet;

    public function __construct(
        array $iconSet,
        string $name = null
    ) {
        parent::__construct($name);
        $this->iconSet = $iconSet;
    }


    protected function configure()
    {
        $this
            ->setName('cms:menu:icon-set-generator')
            ->setDescription('')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);

        $filePath = $io->ask('path of font.scss', 'assets/css/config/_fonts.scss');
        $prefix = $io->ask('icon class prefix', 'icon');
        $outputFile = $io->ask('output file', 'config/packages/wd_cms_menu_icon_set.yaml');
        $merge = $io->confirm('merge with existing icons', true);

        preg_match_all('/\.('.$prefix.'-([\w0-9]+)):/', file_get_contents($filePath), $matches);

        $set = [];

        if ($merge) {
            $set = $this->iconSet;
        }

        foreach ($matches[1] as $key => $match) {
            $set[$match] = $matches[2][$key];
        }

        $output = <<<YAML
parameters:
  wd_cms.menu.icon_set:

YAML;
        foreach ($set as $icon => $label) {
//            \t\t'.$icon.': '.$label.'\n'
            $line = <<<EOT
    $icon: $label

EOT;
            $output .= $line;
        }

        file_put_contents($outputFile, $output);

        return 0;
    }
}
