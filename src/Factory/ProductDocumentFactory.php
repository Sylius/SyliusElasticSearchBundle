<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory;

use ONGR\ElasticsearchBundle\Collection\Collection;
use Ramsey\Uuid\Uuid;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductAttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Sylius\ElasticSearchPlugin\Document\AttributeDocument;
use Sylius\ElasticSearchPlugin\Document\ImageDocument;
use Sylius\ElasticSearchPlugin\Document\OptionDocument;
use Sylius\ElasticSearchPlugin\Document\PriceDocument;
use Sylius\ElasticSearchPlugin\Document\ProductDocument;
use Sylius\ElasticSearchPlugin\Document\ProductTaxonDocument;
use Sylius\ElasticSearchPlugin\Document\TaxonDocument;
use Sylius\ElasticSearchPlugin\Document\VariantDocument;

final class ProductDocumentFactory implements ProductDocumentFactoryInterface
{
    /** @var string */
    private $productDocumentClass;

    /** @var string */
    private $attributeDocumentClass;

    /** @var string */
    private $imageDocumentClass;

    /** @var string */
    private $priceDocumentClass;

    /** @var string */
    private $productTaxonDocumentClass;

    /** @var string */
    private $taxonDocumentClass;

    /** @var string */
    private $variantDocumentClass;

    /** @var string */
    private $optionDocumentClass;

    /** @var array */
    private $attributeWhitelist = [];

    /**
     * @param string $productDocumentClass
     * @param string $attributeDocumentClass
     * @param string $imageDocumentClass
     * @param string $priceDocumentClass
     * @param string $productTaxonDocumentClass
     * @param string $taxonDocumentClass
     * @param string $variantDocumentClass
     * @param string $optionDocumentClass
     * @param array $attributeWhitelist
     */
    public function __construct(
        string $productDocumentClass,
        string $attributeDocumentClass,
        string $imageDocumentClass,
        string $priceDocumentClass,
        string $productTaxonDocumentClass,
        string $taxonDocumentClass,
        string $variantDocumentClass,
        string $optionDocumentClass,
        array $attributeWhitelist
    ) {
        $this->assertClassExtends($productDocumentClass, ProductDocument::class);
        $this->productDocumentClass = $productDocumentClass;

        $this->assertClassExtends($attributeDocumentClass, AttributeDocument::class);
        $this->attributeDocumentClass = $attributeDocumentClass;

        $this->assertClassExtends($imageDocumentClass, ImageDocument::class);
        $this->imageDocumentClass = $imageDocumentClass;

        $this->assertClassExtends($priceDocumentClass, PriceDocument::class);
        $this->priceDocumentClass = $priceDocumentClass;

        $this->assertClassExtends($productTaxonDocumentClass, ProductTaxonDocument::class);
        $this->productTaxonDocumentClass = $productTaxonDocumentClass;

        $this->assertClassExtends($taxonDocumentClass, TaxonDocument::class);
        $this->taxonDocumentClass = $taxonDocumentClass;

        $this->assertClassExtends($variantDocumentClass, VariantDocument::class);
        $this->variantDocumentClass = $variantDocumentClass;

        $this->assertClassExtends($optionDocumentClass, OptionDocument::class);
        $this->optionDocumentClass = $optionDocumentClass;

        $this->attributeWhitelist = $attributeWhitelist;
    }

    /**
     * Create a product document from the product object with all it's related documents
     *
     * @param ProductInterface $product
     * @param LocaleInterface $locale
     * @param ChannelInterface $channel
     *
     * @return ProductDocument
     */
    public function createFromSyliusSimpleProductModel(
        ProductInterface $product,
        LocaleInterface $locale,
        ChannelInterface $channel
    ): ProductDocument {

        $minProductChannelPrice = $this->getChannelPricingForChannelFromProductVariant(
            $product->getVariants()->first(),
            $channel
        );
        /** @var ProductVariantInterface[] $syliusProductVariants */
        $syliusProductVariants = $product->getVariants();

        /**
         * Select minimal product price out of all variants
         */
        $variants = [];
        foreach ($syliusProductVariants as $variant) {
            $channelPrice = $this->getChannelPricingForChannelFromProductVariant($variant, $channel);
            if ($minProductChannelPrice->getPrice() < $channelPrice->getPrice()) {
                $minProductChannelPrice = $channelPrice;
            }
            $variants[] = $this->createVariantDocumentFromSyliusVariant($variant, $channel, $locale);
        }

        /** @var ProductTranslationInterface|TranslationInterface $productTranslation */
        $productTranslation = $product->getTranslation($locale->getCode());
        $syliusProductAttributes = $product->getAttributesByLocale(
            $locale->getCode(),
            $channel->getDefaultLocale()->getCode()
        );

        /** @var ProductDocument $productDocument */
        $productDocument = new $this->productDocumentClass();
        $productDocument->setId(Uuid::uuid4()->toString());
        $productDocument->setEnabled($product->isEnabled());
        $productDocument->setLocaleCode($locale->getCode());
        $productDocument->setSlug($productTranslation->getSlug());
        $productDocument->setName($productTranslation->getName());
        $productDocument->setDescription($productTranslation->getDescription());
        $productDocument->setChannelCode($channel->getCode());
        $productDocument->setCode($product->getCode());
        $productDocument->setCreatedAt($product->getCreatedAt());
        $productDocument->setSynchronisedAt(new \DateTime('now'));
        $productDocument->setAverageReviewRating($product->getAverageRating());
        $productDocument->setVariants(new Collection($variants));

        if (null !== $product->getMainTaxon()) {
            /** @var TaxonDocument $mainTaxonDocument */
            $mainTaxonDocument = new $this->taxonDocumentClass();
            $mainTaxonDocument->setCode($product->getMainTaxon()->getCode());

            /** @var TaxonTranslationInterface $mainTaxonTranslation */
            $mainTaxonTranslation = $product->getMainTaxon()->getTranslation($locale->getCode());

            $mainTaxonDocument->setCode($product->getMainTaxon()->getCode());
            $mainTaxonDocument->setSlug($mainTaxonTranslation->getSlug());
            $mainTaxonDocument->setDescription($mainTaxonTranslation->getDescription());
            $productDocument->setMainTaxon($mainTaxonDocument);
        }

        /** @var PriceDocument $price */
        $price = new $this->priceDocumentClass();
        $price->setAmount($minProductChannelPrice->getPrice());
        $price->setCurrency($channel->getBaseCurrency()->getCode());
        $productDocument->setPrice($price);

        $imageDocuments = [];
        foreach ($product->getImages() as $productImage) {
            foreach ($syliusProductVariants as $variant) {
                if ($variant->hasImage($productImage)) {
                    continue 2;
                }
            }

            /** @var ImageDocument $imageDocument */
            $imageDocument = new $this->imageDocumentClass();
            $imageDocument->setPath($productImage->getPath());
            $imageDocument->setCode($productImage->getType());

            $imageDocuments[] = $imageDocument;
        }
        $productDocument->setImages(new Collection($imageDocuments));

        $taxonDocuments = $productTaxonDocuments = [];
        foreach ($product->getProductTaxons() as $syliusProductTaxon) {
            $taxonDocuments[] = $this->createTaxonDocument($syliusProductTaxon->getTaxon(), $locale->getCode());
            $productTaxonDocuments[] = $this->createProductTaxonDocument($syliusProductTaxon, $locale->getCode());

            $syliusProductTaxonAncestors = $this->getAncestorsFromTaxon($syliusProductTaxon->getTaxon());
            foreach ($syliusProductTaxonAncestors as $syliusProductTaxonAncestor) {
                $taxonDocuments[] = $this->createTaxonDocument($syliusProductTaxonAncestor, $locale->getCode());
            }
        }
        $productDocument->setTaxons(new Collection($taxonDocuments));
        $productDocument->setProductTaxons(new Collection($productTaxonDocuments));

        $productAttributeDocuments = [];
        foreach ($syliusProductAttributes as $syliusProductAttributeValue) {
            if (in_array($syliusProductAttributeValue->getCode(), $this->attributeWhitelist, true)) {
                $productAttributeDocuments = array_merge(
                    $productAttributeDocuments,
                    $this->createAttributeDocumentFromSyliusProductAttributeValue(
                        $locale,
                        $syliusProductAttributeValue
                    )
                );
            }
        }
        $productDocument->setAttributes(new Collection($productAttributeDocuments));

        return $productDocument;
    }

    /**
     * Create a taxon document from a taxon object
     *
     * @param TaxonInterface $taxon
     * @param string $localeCode
     *
     * @return TaxonDocument
     */
    private function createTaxonDocument(TaxonInterface $taxon, ?string $localeCode = null): TaxonDocument
    {
        /** @var TaxonTranslationInterface $taxonTranslation */
        $taxonTranslation = $taxon->getTranslation($localeCode);

        /** @var TaxonDocument $taxonDocument */
        $taxonDocument = new $this->taxonDocumentClass();
        $taxonDocument->setCode($taxon->getCode());
        $taxonDocument->setSlug($taxonTranslation->getSlug());
        $taxonDocument->setPosition($taxon->getPosition());
        $taxonDocument->setDescription($taxonTranslation->getDescription());

        $taxonDocumentImages = [];
        $syliusTaxonImages = $taxon->getImages();
        foreach ($syliusTaxonImages as $syliusTaxonImage) {
            /** @var ImageDocument $taxonDocumentImage */
            $taxonDocumentImage = new $this->imageDocumentClass();
            $taxonDocumentImage->setPath($syliusTaxonImage->getPath());
            $taxonDocumentImage->setCode($syliusTaxonImage->getType());
            $taxonDocumentImages[] = $taxonDocumentImage;
        }
        $taxonDocument->setImages(new Collection($taxonDocumentImages));

        return $taxonDocument;
    }

    /**
     * Create variant document from a product variant with all it's related documents
     *
     * @param ProductVariantInterface $productVariant
     * @param ChannelInterface $channel
     * @param LocaleInterface $locale
     *
     * @return VariantDocument
     */
    private function createVariantDocumentFromSyliusVariant(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel,
        LocaleInterface $locale
    ): VariantDocument {
        /** @var PriceDocument $price */
        $price = new $this->priceDocumentClass();
        $channelPricing = $this->getChannelPricingForChannelFromProductVariant($productVariant, $channel);
        $price->setAmount($channelPricing->getPrice());
        $price->setCurrency($channel->getBaseCurrency()->getCode());

        $images = [];
        foreach ($productVariant->getImages() as $image) {
            /** @var ImageDocument $productImage */
            $productImage = new $this->imageDocumentClass();
            $productImage->setPath($image->getPath());
            $productImage->setCode($image->getType());
            $images[] = $productImage;
        }

        $options = [];
        foreach ($productVariant->getOptionValues() as $optionValue) {
            $options[] = $this->createOptionDocumentFromSyliusOptionValue($optionValue, $locale);
        }

        /** @var VariantDocument $variant */
        $variant = new $this->variantDocumentClass();
        $variant->setCode($productVariant->getCode());
        $variant->setPrice($price);
        $variant->setStock($productVariant->getOnHand() - $productVariant->getOnHold());
        $variant->setIsTracked($productVariant->isTracked());
        $variant->setImages(new Collection($images));
        $variant->setOptions(new Collection($options));

        return $variant;
    }

    /**
     * @param TaxonInterface $taxon
     *
     * @return array
     */
    private function getAncestorsFromTaxon(TaxonInterface $taxon): array
    {
        $ancestors = [];
        while (null !== $taxon->getParent()) {
            $taxon = $taxon->getParent();

            $ancestors[] = $taxon;
        }

        return $ancestors;
    }

    /**
     * @param ProductTaxonInterface $productTaxon
     * @param string $localeCode
     *
     * @return ProductTaxonDocument
     */
    private function createProductTaxonDocument(
        ProductTaxonInterface $productTaxon,
        $localeCode = null
    ): ProductTaxonDocument {
        /** @var TaxonInterface $taxon */
        $taxon = $productTaxon->getTaxon();

        /** @var TaxonTranslationInterface $taxonTranslation */
        $taxonTranslation = $taxon->getTranslation($localeCode);

        /** @var ProductTaxonDocument $productTaxonDocument */
        $productTaxonDocument = new $this->productTaxonDocumentClass();
        $productTaxonDocument->setPosition($productTaxon->getPosition());
        $productTaxonDocument->setCode($taxon->getCode());
        $productTaxonDocument->setSlug($taxonTranslation->getSlug());

        return $productTaxonDocument;
    }

    /**
     * Get price for the pricing document
     *
     * @param ProductVariantInterface $productVariant
     * @param ChannelInterface $channel
     *
     * @return ChannelPricingInterface|null
     */
    private function getChannelPricingForChannelFromProductVariant(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel
    ): ?ChannelPricingInterface
    {
        $channelPricings = $productVariant->getChannelPricings();

        foreach ($channelPricings as $channelPricing) {
            if ($channelPricing->getChannelCode() === $channel->getCode()) {
                return $channelPricing;
            }
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $parentClass
     *
     * @throws \InvalidArgumentException
     */
    private function assertClassExtends(string $class, string $parentClass)
    {
        if ($class !== $parentClass && !in_array($parentClass, class_parents($class), true)) {
            throw new \InvalidArgumentException(sprintf('Class %s MUST extend class %s!', $class, $parentClass));
        }
    }

    /**
     * Create an option document from an option value object
     *
     * @param ProductOptionValueInterface $optionValue
     * @param LocaleInterface $locale
     *
     * @return OptionDocument
     */
    private function createOptionDocumentFromSyliusOptionValue(
        ProductOptionValueInterface $optionValue,
        LocaleInterface $locale
    ): OptionDocument {
        /** @var OptionDocument $option */
        $option = new $this->optionDocumentClass();
        $option->setCode($optionValue->getOptionCode());
        $option->setName($optionValue->getOption()->getTranslation($locale->getCode())->getName());
        $option->setValue($optionValue->getTranslation($locale->getCode())->getValue());

        return $option;
    }

    /**
     * Creates an array of attribute document objects, handles the cases when attribute value is an array
     *
     * @param LocaleInterface $locale
     * @param ProductAttributeValueInterface $syliusProductAttributeValue
     *
     * @return AttributeDocument[]
     */
    private function createAttributeDocumentFromSyliusProductAttributeValue(
        LocaleInterface $locale,
        ProductAttributeValueInterface $syliusProductAttributeValue
    ): array {
        $productAttributes = [];

        if (is_array($syliusProductAttributeValue->getValue())) {
            foreach ($syliusProductAttributeValue->getValue() as $value) {
                $syliusProductAttributeTranslation = $syliusProductAttributeValue->getAttribute()->getTranslation(
                    $locale->getCode()
                );

                if (is_null($syliusProductAttributeTranslation->getName())) {
                    continue;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                $productAttributes[] = $this->createSingleAttributeDocumentFromSyliusProductAttributeValue(
                    (string) $value,
                    $syliusProductAttributeValue,
                    $locale
                );
            }
        } else {
            $syliusProductAttributeTranslation = $syliusProductAttributeValue->getAttribute()->getTranslation(
                $locale->getCode()
            );

            $value = $syliusProductAttributeValue->getValue();

            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            if (!is_null($syliusProductAttributeTranslation->getName())) {
                $productAttributes[] = $this->createSingleAttributeDocumentFromSyliusProductAttributeValue(
                    (string) $value,
                    $syliusProductAttributeValue,
                    $locale
                );
            }
        }

        return $productAttributes;
    }

    /**
     * Creates a single attribute document from product attribute value object
     *
     * @param string $value
     * @param ProductAttributeValueInterface $syliusProductAttributeValue
     * @param LocaleInterface $locale
     *
     * @return AttributeDocument
     */
    public function createSingleAttributeDocumentFromSyliusProductAttributeValue(
        string $value,
        ProductAttributeValueInterface $syliusProductAttributeValue,
        LocaleInterface $locale
    ) {
        /** @var AttributeDocument $productAttribute */
        $productAttribute = new $this->attributeDocumentClass();
        $productAttribute->setCode($syliusProductAttributeValue->getCode());
        $productAttribute->setValue($value);
        /** @var ProductAttributeTranslationInterface $syliusProductAttributeTranslation */
        $syliusProductAttributeTranslation = $syliusProductAttributeValue->getAttribute()->getTranslation(
            $locale->getCode()
        )
        ;

        $productAttribute->setName($syliusProductAttributeTranslation->getName());

        return $productAttribute;
    }
}
