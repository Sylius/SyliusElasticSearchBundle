<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductAttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\ElasticSearchPlugin\Document\AttributeDocumentInterface;

final class AttributeDocumentFactory implements AttributeDocumentFactoryInterface
{
    /** @var string */
    private $attributeDocumentClass;

    public function __construct(string $attributeDocumentClass)
    {
        $this->attributeDocumentClass = $attributeDocumentClass;
    }

    public function create(
        $data,
        LocaleInterface $locale,
        ProductAttributeValueInterface $productAttributeValue
    ): array {
        $productAttributes = [];

        if (is_array($data)) {
            foreach ($data as $value) {
                $productAttributes = array_merge(
                    $productAttributes,
                    $this->create(
                        $value,
                        $locale,
                        $productAttributeValue
                    )
                );
            }
        } else {
            /** @var AttributeDocumentInterface $productAttribute */
            $productAttribute = new $this->attributeDocumentClass();
            $productAttribute->setCode($productAttributeValue->getCode());
            $productAttribute->setValue($data);
            /** @var ProductAttributeTranslationInterface $productAttributeTranslation */
            $productAttributeTranslation = $productAttributeValue->getAttribute()->getTranslation(
                $locale->getCode()
            );
            $productAttribute->setName($productAttributeTranslation->getName());
            $productAttributes = [$productAttribute];
        }

        return $productAttributes;
    }
}
