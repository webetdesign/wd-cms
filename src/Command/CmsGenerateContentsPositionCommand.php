<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManager;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;

class CmsGenerateContentsPositionCommand extends Command
{
    protected static $defaultName = 'cms:gen-contents-position';

    /** @var PDO */
    protected $con;
    protected $em;

    /**
     * @inheritDoc
     */
    public function __construct(?string $name = null, EntityManager $em)
    {
        $this->em = $em;
        parent::__construct($name);
    }


    protected function configure()
    {
        $this
            ->setDescription('Generer les positions des contenus')
            //            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            //            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var CmsContentRepository $repo */
        $repo = $this->em->getRepository(CmsContent::class);

        $qb = $repo->createQueryBuilder('c');
        $qb->where($qb->expr()->isNotNull('c.page'));

        $contents = $qb->getQuery()->getResult();

        $this->processContents($contents, 'getPage');

        $qb = $repo->createQueryBuilder('c');
        $qb->where($qb->expr()->isNotNull('c.sharedBlockParent'));

        $contents = $qb->getQuery()->getResult();

        $this->processContents($contents, 'getSharedBlockParent');

        $qb = $repo->createQueryBuilder('c');
        $qb->where($qb->expr()->isNotNull('c.declination'));

        $contents = $qb->getQuery()->getResult();

        $this->processContents($contents, 'getDeclination');

    }

    protected function processContents($contents, $getter)
    {
        $groups = [];
        /** @var CmsContent $content */
        foreach ($contents as $content) {
            if (!isset($groups[$content->$getter()->getId()])) {
                $groups[$content->$getter()->getId()] = [];
            }
            $groups[$content->$getter()->getId()][] = $content;
        }

        foreach ($groups as $group) {
            foreach ($group as $key => $content) {
                $content->setPosition($key + 1);
            }
            $this->em->persist($content);
        }

        $this->em->flush();
    }
}
