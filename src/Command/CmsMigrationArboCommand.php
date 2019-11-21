<?php

namespace WebEtDesign\CmsBundle\Command;

use Doctrine\ORM\EntityManager;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;

class CmsMigrationArboCommand extends Command
{
    protected static $defaultName = 'cms:migration-arbo';

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
            ->setDescription('Add a short description for your command')
            //            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            //            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

//        $this->con = $this->getConnection($io);
        $oSites   = $this->em->getRepository('WebEtDesignCmsBundle:CmsSite')->findAll();
        $menuRepo = $this->em->getRepository('WebEtDesignCmsBundle:CmsMenu');
        if (empty($oSites)) {
            $oMenus = $menuRepo->findAll();
            if (!empty($oMenus)) {
                $rootMenu = $oMenus[0]->getRoot();
            }

            $newSite = new CmsSite();
            $newSite->setLabel('Default');
            $newSite->setDefault(true);

            $this->em->persist($newSite);
            $this->em->flush();

            if (isset($rootMenu)) {
                $uselessMenu = $newSite->getMenu();
                $newSite->setMenu($rootMenu);
                $this->em->persist($newSite);
                $this->em->remove($uselessMenu);

                $this->em->flush();
            }

            /** @var CmsPage $root */
            $root = $newSite->getRoot();

            $pageRepo = $this->em->getRepository('WebEtDesignCmsBundle:CmsPage');
            $pages    = $pageRepo->findBy(['root' => null]);
            /** @var CmsPage $page */
            foreach ($pages as $page) {
                if ($page == $root) {
                    return;
                }
                $pageRepo->persistAsLastChildOf($page, $root);
            }

            $this->em->flush();
        }

        $io->success('Migration terminée');
    }

    public function getSites()
    {
        $stmt = $this->con->prepare('SELECT * from cms__site; ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getPages()
    {
        $stmt = $this->con->prepare('SELECT * from cms__page; ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getContents()
    {
        $stmt = $this->con->prepare('SELECT * from cms__content; ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getMenus()
    {
        $stmt = $this->con->prepare('SELECT * from cms__menu; ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getConnection(SymfonyStyle $io)
    {
        $io->section('Information sur la base de test');
        $name     = $io->ask('Nom de la base de test', 'autojm-v3-test');
        $host     = $io->ask('host', '127.0.0.1');
        $port     = $io->ask('port', '3306');
        $user     = $io->ask('user', 'root');
        $password = $io->ask('password', 'root');

        return new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $password);
    }
}
