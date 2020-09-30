<?php

namespace WebEtDesign\CmsBundle\Handler;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Sluggable\Handler\SlugHandlerInterface;
use Gedmo\Sluggable\SluggableListener;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Repository\CmsPageRepository;

/**
 * Permets la génération du slug de page identique pour des sites différents
 */
class CmsPageSlugHandler implements SlugHandlerInterface
{
    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SluggableListener
     */
    protected $sluggable;

    /**
     * Callable of original transliterator
     * which is used by sluggable
     *
     * @var callable
     */
    private $originalTransliterator;

    public function __construct(\Gedmo\Sluggable\SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    public function postSlugBuild(\Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->originalTransliterator = $this->sluggable->getTransliterator();
        $this->sluggable->setTransliterator([$this, 'transliterate']);
    }

    /**
     * @param $text
     * @param $separator
     * @param CmsPage $object
     * @return string
     */
    public function transliterate($text, $separator, $object)
    {
        $slugify = new Slugify();
        $res    = $slugify->slugify($text);

        $same = $this->om->getRepository(CmsPage::class)->findBy([
            'slug' => $res,
            'site' => $object->getSite()
        ]);

        if ($same) {
            $res = $res . $separator . count($same);
        }

        $this->sluggable->setTransliterator($this->originalTransliterator);

        return $res;
    }

    /**
     * Callback on slug handlers before the decision
     * is made whether or not the slug needs to be
     * recalculated
     *
     * @param \Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea
     * @param array $config
     * @param CmsPage $object
     * @param string $slug
     * @param boolean $needToChangeSlug
     *
     * @return void
     */
    public function onChangeDecision(\Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
        $this->om         = $ea->getObjectManager();
        $needToChangeSlug = true;
    }

    /**
     * Callback for slug handlers on slug completion
     *
     * @param \Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function onSlugCompletion(\Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea, array &$config, $object, &$slug)
    {
    }

    /**
     * @return boolean whether or not this handler has already urlized the slug
     */
    public function handlesUrlization()
    {
        return true;
    }

    /**
     * Validate handler options
     *
     * @param array $options
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     */
    public static function validate(array $options, \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta)
    {
    }
}
