<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\ElasticSearchPlugin\Document\OptionDocumentInterface;

interface OptionDocumentFactoryInterface
{
    public function create(
        ProductOptionValueInterface $optionValue,
        LocaleInterface $locale
    ): OptionDocumentInterface;
}
