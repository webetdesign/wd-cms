<?php

namespace WebEtDesign\CmsBundle\Provider;

use Sonata\Form\Validator\ErrorElement;
use Sonata\MediaBundle\Provider\FileProvider;
use Gaufrette\Filesystem;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\MediaBundle\CDN\CDNInterface;
use Sonata\MediaBundle\Generator\GeneratorInterface;
use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Sonata\MediaBundle\Provider\Metadata;
use Sonata\MediaBundle\Thumbnail\ThumbnailInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class SvgProvider extends FileProvider
{
    protected $allowedMimeTypes;
    protected $allowedExtensions;
    protected $metadata;

    public function __construct(
        $name,
        Filesystem $filesystem,
        CDNInterface $cdn,
        GeneratorInterface $pathGenerator,
        ThumbnailInterface $thumbnail,
        array $allowedExtensions = [],
        array $allowedMimeTypes = [],
        MetadataBuilderInterface $metadata = null
    ) {
        parent::__construct($name, $filesystem, $cdn, $pathGenerator, $thumbnail);


        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes  = $allowedMimeTypes;
        $this->metadata          = $metadata;

    }

    public function buildCreateForm(FormMapper $formMapper)
    {
        $formMapper->add('binaryContent', FileType::class, [
            'label' => 'Upload SVG file only',
            'constraints' => [
                new NotBlank(),
                new NotNull(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(ErrorElement $errorElement, MediaInterface $media)
    {

        if (!$media->getBinaryContent() instanceof \SplFileInfo) {
            return;
        }

        if ($media->getBinaryContent() instanceof UploadedFile) {
            $fileName = $media->getBinaryContent()->getClientOriginalName();
        } elseif ($media->getBinaryContent() instanceof File) {
            $fileName = $media->getBinaryContent()->getFilename();
        } else {
            throw new \RuntimeException(sprintf('Invalid binary content type: %s',
                get_class($media->getBinaryContent())));
        }

        if (!in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $this->allowedExtensions)) {
            $errorElement
                ->with('binaryContent')
                ->addViolation('Invalid extensions')
                ->end();
        }

        if (!in_array($media->getBinaryContent()->getMimeType(), $this->allowedMimeTypes)) {
            $errorElement
                ->with('binaryContent')
                ->addViolation('Invalid mime type : '.$media->getBinaryContent()->getMimeType())
                ->end();
        }
    }

    protected function generateReferenceName(MediaInterface $media)
    {
        return $this->generateMediaUniqId($media).'.svg';
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl(MediaInterface $media, $format)
    {
        $path = $this->getReferenceImage($media);

        return $this->getCdn()->getPath($path, $media->getCdnIsFlushable());
    }


    public function getProviderMetadata()
    {
        return new Metadata(
            $this->getName(),
            $this->getName().'.description',
            null,
            'wd_cms',
            ['class' => 'fa fa-expand']
        );
    }
}
