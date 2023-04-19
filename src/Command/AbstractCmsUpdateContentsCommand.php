<?php


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\CMS\Template\ComponentInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;

abstract class AbstractCmsUpdateContentsCommand extends Command
{
    protected SymfonyStyle $io;

    protected EntityManagerInterface $em;

    protected CmsContentRepository $contentRp;

    public function __construct(
        EntityManagerInterface $em,
        string $name = null
    ) {
        parent::__construct($name);
        $this->em = $em;
    }

    protected function init($input, $output)
    {
        $this->contentRp = $this->em->getRepository(CmsContent::class);
        $this->io        = new SymfonyStyle($input, $output);
    }

    protected function processContent($object, ComponentInterface $config)
    {
        foreach ($config->getBlocks() as $block) {
            $contentConf[$block->getCode()] = $block;
        }

        $codes = array_keys($contentConf ?? []);

        if (count($codes) == 0) {
            return true;
        }

        $ins  = $this->contentRp->findByParentInOutCodes($object, $codes, 'IN');
        $outs = $this->contentRp->findByParentInOutCodes($object, $codes, 'OUT');

        foreach ($outs as $out) {
            $this->em->remove($out);
        }

        $contentDone = [];

        /** @var CmsContent $in */
        foreach ($ins as $in) {
            if (!isset($contentConf[$in->getCode()])) {
                $this->em->remove($in);
                continue;
            }
            $conf          = $contentConf[$in->getCode()];
            $contentDone[] = $in->getCode();
            $in->setPosition(array_search($in->getCode(), $codes));
            $in->setLabel($conf->getLabel());

            if ($in->getType() !== $conf->getType()) {
                $in->setValue(null);
                $in->setType($conf->getType());
            }
            $this->em->persist($in);
        }

        foreach (array_diff($codes, $contentDone) as $code) {
            $conf    = $contentConf[$code];
            $content = new CmsContent();
            $content->setPosition(array_search($code, $codes));
            $content->setCode($code);
            $content->setLabel($conf->getLabel());
            $content->setType($conf->getType());
            if ($object instanceof CmsPage) {
                $content->setPage($object);
            }
            if ($object instanceof CmsPageDeclination) {
                $content->setDeclination($object);
            }
            if ($object instanceof CmsSharedBlock) {
                $content->setSharedBlockParent($object);
            }

            $this->em->persist($content);
        }

        $this->em->flush();
        return true;
    }
}
