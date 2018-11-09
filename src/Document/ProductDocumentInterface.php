<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

use Doctrine\Common\Collections\Collection;

interface  ProductDocumentInterface
{
    /**
     * @return string
     */
    public function getUuid(): string;

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void;

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param mixed $id
     */
    public function setId($id): void;

    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @param string $code
     */
    public function setCode(string $code): void;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void;

    /**
     * @return string
     */
    public function getChannelCode(): string;

    /**
     * @param string $channelCode
     */
    public function setChannelCode(string $channelCode): void;

    /**
     * @return string
     */
    public function getLocaleCode(): string;

    /**
     * @param string $localeCode
     */
    public function setLocaleCode(string $localeCode): void;

    /**
     * @return string
     */
    public function getDescription(): ?string;

    /**
     * @param string $description
     */
    public function setDescription(?string $description): void;

    /**
     * @return PriceDocument
     */
    public function getPrice(): PriceDocumentInterface;

    /**
     * @param PriceDocumentInterface $price
     */
    public function setPrice(PriceDocumentInterface $price): void;

    /**
     * @return TaxonDocumentInterface
     */
    public function getMainTaxon(): ?TaxonDocumentInterface;

    /**
     * @param TaxonDocumentInterface $mainTaxon
     */
    public function setMainTaxon(TaxonDocumentInterface $mainTaxon): void;

    /**
     * @return Collection|TaxonDocumentInterface[]
     */
    public function getTaxons(): Collection;

    /**
     * @param Collection|TaxonDocument[] $taxons
     */
    public function setTaxons(Collection $taxons): void;

    /**
     * @return Collection|AttributeDocumentInterface[]
     */
    public function getAttributes(): Collection;

    /**
     * @param Collection $attributes
     */
    public function setAttributes(Collection $attributes): void;

    /**
     * @return Collection|ImageDocumentInterface[]
     */
    public function getImages(): Collection;

    /**
     * @param Collection $images
     */
    public function setImages(Collection $images): void;

    /**
     * @return float
     */
    public function getAverageReviewRating(): ?float;

    /**
     * @param float $averageReviewRating
     */
    public function setAverageReviewRating(float $averageReviewRating): void;

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void;

    /**
     * @return \DateTimeInterface
     */
    public function getSynchronisedAt(): \DateTimeInterface;

    /**
     * @param \DateTimeInterface $synchronisedAt
     */
    public function setSynchronisedAt(\DateTimeInterface $synchronisedAt): void;

    /**
     * @return Collection|VariantDocumentInterface[]
     */
    public function getVariants(): Collection;

    /**
     * @param Collection $variants
     */
    public function setVariants(Collection $variants): void;
}
