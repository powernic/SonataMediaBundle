<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Model;

use Imagine\Image\Box;
use Sonata\ClassificationBundle\Model\CategoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @method void setCategory($category)
 */
abstract class Media implements MediaInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var string
     */
    protected $providerName;

    /**
     * @var int
     */
    protected $providerStatus;

    /**
     * @var string
     */
    protected $providerReference;

    /**
     * @var array
     */
    protected $providerMetadata = [];

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var float
     */
    protected $length;

    /**
     * @var string
     */
    protected $copyright;

    /**
     * @var string
     */
    protected $authorName;

    /**
     * @var string
     */
    protected $context;

    /**
     * @var bool
     */
    protected $cdnIsFlushable;

    /**
     * @var string
     */
    protected $cdnFlushIdentifier;

    /**
     * @var \DateTime
     */
    protected $cdnFlushAt;

    /**
     * @var int
     */
    protected $cdnStatus;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var mixed
     */
    protected $binaryContent;

    /**
     * @var string
     */
    protected $previousProviderReference;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var GalleryHasMediaInterface[]
     */
    protected $galleryHasMedias;

    /**
     * @var CategoryInterface
     */
    protected $category;

    public function __toString()
    {
        return $this->getName() ?? 'n/a';
    }

    // NEXT_MAJOR: Remove this method
    public function __set($property, $value)
    {
        if ('category' === $property) {
            if (null !== $value && !is_a($value, CategoryInterface::class)) {
                throw new \InvalidArgumentException(
                    '$category should be an instance of Sonata\ClassificationBundle\Model\CategoryInterface or null'
                );
            }

            $this->category = $value;
        }
    }

    // NEXT_MAJOR: Remove this method
    public function __call($method, $arguments)
    {
        if ('setCategory' === $method) {
            $this->__set('category', current($arguments));
        }
    }

    public function prePersist()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
    }

    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @static
     *
     * @return string[]
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_OK => 'ok',
            self::STATUS_SENDING => 'sending',
            self::STATUS_PENDING => 'pending',
            self::STATUS_ERROR => 'error',
            self::STATUS_ENCODING => 'encoding',
        ];
    }

    public function setBinaryContent($binaryContent)
    {
        $this->previousProviderReference = $this->providerReference;
        $this->providerReference = null;
        $this->binaryContent = $binaryContent;
    }

    public function resetBinaryContent()
    {
        $this->binaryContent = null;
    }

    public function getBinaryContent()
    {
        return $this->binaryContent;
    }

    public function getMetadataValue($name, $default = null)
    {
        $metadata = $this->getProviderMetadata();

        return $metadata[$name] ?? $default;
    }

    public function setMetadataValue($name, $value)
    {
        $metadata = $this->getProviderMetadata();
        $metadata[$name] = $value;
        $this->setProviderMetadata($metadata);
    }

    public function unsetMetadataValue($name)
    {
        $metadata = $this->getProviderMetadata();
        unset($metadata[$name]);
        $this->setProviderMetadata($metadata);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setProviderName($providerName)
    {
        $this->providerName = $providerName;
    }

    public function getProviderName()
    {
        return $this->providerName;
    }

    public function setProviderStatus($providerStatus)
    {
        $this->providerStatus = $providerStatus;
    }

    public function getProviderStatus()
    {
        return $this->providerStatus;
    }

    public function setProviderReference($providerReference)
    {
        $this->providerReference = $providerReference;
    }

    public function getProviderReference()
    {
        return $this->providerReference;
    }

    public function setProviderMetadata(array $providerMetadata = [])
    {
        $this->providerMetadata = $providerMetadata;
    }

    public function getProviderMetadata()
    {
        return $this->providerMetadata;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setLength($length)
    {
        $this->length = $length;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
    }

    public function getCopyright()
    {
        return $this->copyright;
    }

    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;
    }

    public function getAuthorName()
    {
        return $this->authorName;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setCdnIsFlushable($cdnIsFlushable)
    {
        $this->cdnIsFlushable = $cdnIsFlushable;
    }

    public function getCdnIsFlushable()
    {
        return $this->cdnIsFlushable;
    }

    public function setCdnFlushIdentifier($cdnFlushIdentifier)
    {
        $this->cdnFlushIdentifier = $cdnFlushIdentifier;
    }

    public function getCdnFlushIdentifier()
    {
        return $this->cdnFlushIdentifier;
    }

    public function setCdnFlushAt(?\DateTime $cdnFlushAt = null)
    {
        $this->cdnFlushAt = $cdnFlushAt;
    }

    public function getCdnFlushAt()
    {
        return $this->cdnFlushAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setCreatedAt(?\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getExtension()
    {
        $providerReference = $this->getProviderReference();
        if (!$providerReference) {
            return null;
        }

        // strips off query strings or hashes, which are common in URIs remote references
        return preg_replace('{(\?|#).*}', '', pathinfo($providerReference, \PATHINFO_EXTENSION));
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setCdnStatus($cdnStatus)
    {
        $this->cdnStatus = $cdnStatus;
    }

    public function getCdnStatus()
    {
        return $this->cdnStatus;
    }

    public function getBox()
    {
        return new Box($this->width, $this->height);
    }

    public function setGalleryHasMedias($galleryHasMedias)
    {
        $this->galleryHasMedias = $galleryHasMedias;
    }

    public function getGalleryHasMedias()
    {
        return $this->galleryHasMedias;
    }

    public function getPreviousProviderReference()
    {
        return $this->previousProviderReference;
    }

    /**
     * NEXT_MAJOR: Remove this method when bumping Symfony requirement to 2.8+.
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new Assert\Callback('isStatusErroneous'));
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function isStatusErroneous($context)
    {
        if (null !== $this->getBinaryContent() && self::STATUS_ERROR === $this->getProviderStatus()) {
            // NEXT_MAJOR: Restore type hint
            if (!$context instanceof ExecutionContextInterface) {
                throw new \InvalidArgumentException('Argument 1 should be an instance of Symfony\Component\Validator\ExecutionContextInterface');
            }

            $context->buildViolation('invalid')->atPath('binaryContent')->addViolation();
        }
    }

    /**
     * @return CategoryInterface
     */
    public function getCategory()
    {
        return $this->category;
    }

    // NEXT_MAJOR: Uncomment this method and remove __call and __set
    // /**
    //  * @param CategoryInterface|null $category
    //  */
    // public function setCategory($category = null)
    // {
    //     $this->category = $category;
    // }
}
