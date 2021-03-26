<?php


namespace WebEtDesign\CmsBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Repository\CmsContentRepository;
use WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

abstract class AbstractCmsUpdateContentsCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var CmsContentRepository
     */
    protected $contentRp;

    /**
     * @var TemplateProvider
     */
    protected $templateProvider;

    public function __construct(string $name = null, EntityManagerInterface $em, TemplateProvider $templateProvider)
    {
        parent::__construct($name);
        $this->em               = $em;
        $this->templateProvider = $templateProvider;
    }

    protected function init($input, $output)
    {
        $this->contentRp = $this->em->getRepository(CmsContent::class);
        $this->io        = new SymfonyStyle($input, $output);
    }

    protected function selectTemplate(): string
    {
        $templates = $this->templateProvider->getTemplateList();

        return $this->io->choice('Template', array_flip($templates));
    }

    protected function processContent($object, $config)
    {
        $contentConf = [];
        foreach ($config['contents'] as $content) {
            $contentConf[$content['code']] = $content;
        }
        $codes = array_keys($contentConf);
        
        if(count($codes) == 0){
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
            if(!isset($contentConf[$in->getCode()])){
                $this->em->remove($in);
                continue;
            }
            $conf          = $contentConf[$in->getCode()];
            $contentDone[] = $in->getCode();
            $in->setPosition(array_search($in->getCode(), $codes));
            if (isset($conf['label'])) {
                $in->setLabel($conf['label']);
            }
            if (isset($conf['help'])) {
                $in->setHelp($conf['help']);
            }

            if ($in->getType() !== $conf['type']) {
                $in->setValue(null);
                $in->setMedia(null);
                $in->setSharedBlockList(null);
                $in->setType($conf['type']);
            }
            $this->em->persist($in);
        }

        foreach (array_diff($codes, $contentDone) as $code) {
            $conf    = $contentConf[$code];
            $content = new CmsContent();
            $content->setPosition(array_search($code, $codes));
            $content->setCode($code);
            $content->setType($conf['type']);
            if ($object instanceof CmsPage) {
                $content->setPage($object);
            }
            if ($object instanceof CmsPageDeclination) {
                $content->setDeclination($object);
            }
            if ($object instanceof CmsSharedBlock) {
                $content->setSharedBlockParent($object);
            }
            if (isset($conf['label'])) {
                $content->setLabel($conf['label']);
            } else {
                $content->setLabel($code);
            }
            if (isset($conf['help'])) {
                $content->setHelp($conf['help']);
            }

            $this->em->persist($content);
        }

        $this->em->flush();
        return true;
    }
}
