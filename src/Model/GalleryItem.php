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

abstract class GalleryItem implements GalleryItemInterface
{
    /**
     * @var MediaInterface|null
     */
    protected $media;

    /**
     * @var GalleryInterface|null
     */
    protected $gallery;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $createdAt;

    /**
     * @var bool
     */
    protected $enabled = false;

    public function __toString()
    {
        return $this->getGallery().' | '.(string) ($this->getMedia() ?? '');
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt = null): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setEnabled($enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setGallery(?GalleryInterface $gallery = null): void
    {
        $this->gallery = $gallery;
    }

    public function getGallery()
    {
        return $this->gallery;
    }

    public function setMedia(?MediaInterface $media = null): void
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setPosition($position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
