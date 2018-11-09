<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\ElasticSearchPlugin\Document\VariantDocumentInterface;

interface VariantDocumentFactoryInterface
{
    public function create(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel,
        LocaleInterface $locale
    ): VariantDocumentInterface;
}
