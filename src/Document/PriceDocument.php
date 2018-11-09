<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

use ONGR\ElasticsearchBundle\Annotation as ElasticSearch;

/**
 * @ElasticSearch\ObjectType
 */
class PriceDocument implements PriceDocumentInterface
{
    /**
     * @var int
     *
     * @ElasticSearch\Property(type="integer")
     */
    protected $amount;

    /**
     * @var int
     *
     * @ElasticSearch\Property(type="integer")
     */
    protected $originalAmount = 0;

    /**
     * @var string
     *
     * @ElasticSearch\Property(type="keyword")
     */
    protected $currency;

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getOriginalAmount(): int
    {
        return $this->originalAmount;
    }

    /**
     * @param int $originalAmount
     */
    public function setOriginalAmount(int $originalAmount = 0): void
    {
        $this->originalAmount = $originalAmount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
