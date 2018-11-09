<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

use ONGR\ElasticsearchBundle\Annotation as ElasticSearch;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ElasticSearch\NestedType
 */
class VariantDocument implements VariantDocumentInterface
{
    /**
     * @var mixed
     *
     * @ElasticSearch\Property(type="keyword")
     */
    protected $id;

    /**
     * @var Collection
     *
     * @ElasticSearch\Embedded(class="Sylius\ElasticSearchPlugin\Document\ImageDocument", multiple=true)
     */
    protected $images;

    /**
     * @var PriceDocumentInterface
     *
     * @ElasticSearch\Embedded(class="Sylius\ElasticSearchPlugin\Document\PriceDocument")
     */
    protected $price;

    /**
     * @var string
     *
     * @ElasticSearch\Property(type="keyword")
     */
    protected $code;

    /**
     * @var string
     *
     * @ElasticSearch\Property(type="text")
     */
    protected $name;

    /**
     * @var int
     *
     * @ElasticSearch\Property(type="integer")
     */
    protected $stock;

    /**
     * @var bool
     *
     * @ElasticSearch\Property(type="boolean")
     */
    protected $isTracked;

    /**
     * @var Collection|OptionDocument[]
     *
     * @ElasticSearch\Embedded(class="Sylius\ElasticSearchPlugin\Document\OptionDocument", multiple=true)
     */
    protected $options;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->options = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return Collection
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    /**
     * @param Collection $images
     */
    public function setImages(Collection $images): void
    {
        $this->images = $images;
    }

    /**
     * @return PriceDocumentInterface
     */
    public function getPrice(): PriceDocumentInterface
    {
        return $this->price;
    }

    /**
     * @param PriceDocumentInterface $price
     */
    public function setPrice(PriceDocumentInterface $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getStock(): int
    {
        return $this->stock;
    }

    /**
     * @param int $stock
     */
    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getIsTracked(): bool
    {
        return $this->isTracked;
    }

    /**
     * @return bool
     */
    public function isTracked(): bool
    {
        return $this->getIsTracked();
    }

    /**
     * @param bool $isTracked
     */
    public function setIsTracked(bool $isTracked): void
    {
        $this->isTracked = $isTracked;
    }

    /**
     * @return Collection|OptionDocument[]
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @param Collection $options
     */
    public function setOptions(Collection $options): void
    {
        $this->options = $options;
    }
}
