<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

interface PriceDocumentInterface
{
    /**
     * @return int
     */
    public function getAmount();

    /**
     * @param int $amount
     */
    public function setAmount($amount): void;

    /**
     * @return int
     */
    public function getOriginalAmount(): int;

    /**
     * @param int $originalAmount
     */
    public function setOriginalAmount(int $originalAmount = 0): void;

    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void;
}
