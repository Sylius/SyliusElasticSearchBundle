<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\ElasticSearchPlugin\Document\OptionDocumentInterface;

final class OptionDocumentFactory implements OptionDocumentFactoryInterface
{
    /** @var string */
    private $optionDocumentClass;

    public function __construct(string $optionDocumentClass)
    {
        $this->optionDocumentClass = $optionDocumentClass;
    }

    public function create(
        ProductOptionValueInterface $optionValue,
        LocaleInterface $locale
    ): OptionDocumentInterface {
        /** @var ProductOptionValueTranslationInterface $optionValueTranslation */
        $optionValueTranslation = $optionValue->getTranslation($locale->getCode());

        /** @var ProductOptionTranslationInterface $productOptionTranslation */
        $productOptionTranslation = $optionValue->getOption()->getTranslation($locale->getCode());

        /** @var OptionDocumentInterface $option */
        $option = new $this->optionDocumentClass();
        $option->setCode($optionValue->getOptionCode());
        $option->setName($productOptionTranslation->getName());
        $option->setValue($optionValueTranslation->getValue());

        return $option;
    }
}
