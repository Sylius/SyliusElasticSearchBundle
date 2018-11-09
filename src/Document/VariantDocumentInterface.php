<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

use Doctrine\Common\Collections\Collection;

interface VariantDocumentInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param mixed $id
     */
    public function setId($id): void;

    /**
     * @return Collection
     */
    public function getImages(): Collection;

    /**
     * @param Collection $images
     */
    public function setImages(Collection $images): void;

    /**
     * @return PriceDocumentInterface
     */
    public function getPrice(): PriceDocumentInterface;

    /**
     * @param PriceDocumentInterface $price
     */
    public function setPrice(PriceDocumentInterface $price): void;

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
     * @return int
     */
    public function getStock(): int;

    /**
     * @param int $stock
     */
    public function setStock(int $stock): void;

    /**
     * @return bool
     */
    public function getIsTracked(): bool;

    /**
     * @return bool
     */
    public function isTracked(): bool;

    /**
     * @param bool $isTracked
     */
    public function setIsTracked(bool $isTracked): void;

    /**
     * @return Collection|OptionDocumentInterface[]
     */
    public function getOptions(): Collection;

    /**
     * @param Collection $options
     */
    public function setOptions(Collection $options): void;
}
