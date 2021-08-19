<?php

namespace WebEtDesign\CmsBundle\Handler;

use Cocur\Slugify\Slugify;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Sluggable\Handler\SlugHandlerInterface;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\SluggableListener;
use WebEtDesign\CmsBundle\Entity\CmsPage;

/**
 * Permets la génération du slug de page identique pour des sites différents
 */
class CmsPageSlugHandler implements SlugHandlerInterface
{
    protected ObjectManager $om;

    protected SluggableListener $sluggable;

    /**
     * Callable of original transliterator
     * which is used by sluggable
     *
     * @var callable
     */
    private $originalTransliterator;

    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
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
    public function transliterate($text, $separator, CmsPage $object)
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
     * @param SluggableAdapter $ea
     * @param array $config
     * @param CmsPage $object
     * @param string $slug
     * @param boolean $needToChangeSlug
     *
     * @return void
     */
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
        $this->om         = $ea->getObjectManager();
        $needToChangeSlug = true;
    }

    /**
     * Callback for slug handlers on slug completion
     *
     * @param SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
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
     * @param ClassMetadata $meta
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
    }
}
