<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\ElasticSearchPlugin\Document\ImageDocumentInterface;
use Sylius\ElasticSearchPlugin\Document\ProductDocumentInterface;
use Sylius\ElasticSearchPlugin\Document\TaxonDocumentInterface;
use Zend\Stdlib\ArrayObject;

final class ProductDocumentFactory implements ProductDocumentFactoryInterface
{
    /** @var string */
    private $productDocumentClass;

    /** @var AttributeDocumentFactoryInterface */
    private $attributeDocumentFactory;

    /** @var ImageDocumentFactoryInterface */
    private $imageDocumentFactory;

    /** @var PriceDocumentFactoryInterface */
    private $priceDocumentFactory;

    /** @var string */
    private $taxonDocumentFactory;

    /** @var VariantDocumentFactoryInterface */
    private $variantDocumentFactory;

    /** @var array */
    private $attributeWhitelist = [];

    /**
     * @param string $productDocumentClass
     * @param AttributeDocumentFactoryInterface $attributeDocumentFactory
     * @param ImageDocumentFactoryInterface $imageDocumentFactory
     * @param PriceDocumentFactoryInterface $priceDocumentFactory
     * @param TaxonDocumentFactoryInterface $taxonDocumentFactory
     * @param VariantDocumentFactoryInterface $variantDocumentFactory
     * @param array $attributeWhitelist
     */
    public function __construct(
        string $productDocumentClass,
        AttributeDocumentFactoryInterface $attributeDocumentFactory,
        ImageDocumentFactoryInterface $imageDocumentFactory,
        PriceDocumentFactoryInterface $priceDocumentFactory,
        TaxonDocumentFactoryInterface $taxonDocumentFactory,
        VariantDocumentFactoryInterface $variantDocumentFactory,
        array $attributeWhitelist
    ) {
        $this->assertClassImplements($productDocumentClass, ProductDocumentInterface::class);
        $this->productDocumentClass = $productDocumentClass;

        $this->attributeDocumentFactory = $attributeDocumentFactory;
        $this->imageDocumentFactory = $imageDocumentFactory;
        $this->priceDocumentFactory = $priceDocumentFactory;
        $this->taxonDocumentFactory = $taxonDocumentFactory;
        $this->variantDocumentFactory = $variantDocumentFactory;
        $this->attributeWhitelist = $attributeWhitelist;
    }

    /**
     * Create a product document from the product object with all it's related documents
     *
     * @param ProductInterface $product
     * @param LocaleInterface $locale
     * @param ChannelInterface $channel
     *
     * @return ProductDocumentInterface
     */
    public function create(
        ProductInterface $product,
        LocaleInterface $locale,
        ChannelInterface $channel
    ): ProductDocumentInterface {
        /** @var ProductVariantInterface[] $productVariants */
        $productVariants = $product->getVariants()->filter(function (ProductVariantInterface $productVariant) use ($channel): bool {
            return $productVariant->hasChannelPricingForChannel($channel);
        });

        /**
         * @var ArrayObject
         */
        $iterator = $productVariants->getIterator();
        $iterator->uasort(
            function (ProductVariantInterface $a, ProductVariantInterface $b) {
                return $a->getName() <=> $b->getName();
            }
        );
        $variantDocuments = [];
        foreach ($iterator as $variant) {
            $variantDocuments[] = $this->variantDocumentFactory->create($variant, $channel, $locale);
        }

        /** @var ImageDocumentInterface[] $imageDocuments */
        $imageDocuments = [];
        foreach ($product->getImages() as $productImage) {
            foreach ($productVariants as $variant) {
                if ($variant->hasImage($productImage)) {
                    continue 2;
                }
            }

            $imageDocuments[] = $this->imageDocumentFactory->create($productImage);
        }

        /** @var TaxonDocumentInterface[] $taxonDocuments */
        $taxonDocuments = [];
        foreach ($product->getProductTaxons() as $syliusProductTaxon) {
            $taxonDocuments[] = $this->taxonDocumentFactory->create(
                $syliusProductTaxon->getTaxon(),
                $locale,
                $syliusProductTaxon->getPosition()
            );
        }

        /** @var ProductTranslationInterface|TranslationInterface $productTranslation */
        $productTranslation = $product->getTranslation($locale->getCode());

        $attributeDocuments = $this->getAttributeDocuments($product, $locale, $channel);

        /** @var ProductDocumentInterface $productDocument */
        $productDocument = new $this->productDocumentClass();
        $productDocument->setUuid(Uuid::uuid4()->toString());
        $productDocument->setId($product->getId());
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
        $productDocument->setVariants(new ArrayCollection($variantDocuments));
        $productDocument->setImages(new ArrayCollection($imageDocuments));
        $productDocument->setTaxons(new ArrayCollection($taxonDocuments));
        $productDocument->setAttributes(new ArrayCollection($attributeDocuments));

        /**
         * Set smallest product variant price, used for search by price
         */
        $productDocument->setPrice(
            $this->priceDocumentFactory->create(
                $this->getMinimalPriceFromVariants($productVariants, $channel),
                $channel->getBaseCurrency()
            )
        );

        if (null !== $product->getMainTaxon()) {
            $productDocument->setMainTaxon(
                $this->taxonDocumentFactory->create($product->getMainTaxon(), $locale)
            );
        }

        return $productDocument;
    }

    /**
     * @param ProductVariantInterface[]|Collection $variants
     * @param ChannelInterface $channel
     *
     * @return ChannelPricingInterface
     */
    private function getMinimalPriceFromVariants($variants, ChannelInterface $channel): ChannelPricingInterface
    {
        /** @var ChannelPricingInterface $minProductChannelPrice */
        $minProductChannelPrice = $variants->first()->getChannelPricingForChannel($channel);

        foreach ($variants as $variant) {
            $channelPrice = $variant->getChannelPricingForChannel($channel);
            if (
                ($variant->isTracked() && $variant->getOnHold() - $variant->getOnHold(
                    ) > 0 && $minProductChannelPrice->getPrice() < $channelPrice->getPrice())
                || (!$variant->isTracked() && $minProductChannelPrice->getPrice() < $channelPrice->getPrice())
            ) {
                $minProductChannelPrice = $channelPrice;
            }
        }

        return $minProductChannelPrice;
    }

    /**
     * @param string $class
     * @param string $parentClass
     *
     * @throws \InvalidArgumentException
     */
    private function assertClassImplements(string $class, string $parentClass)
    {
        if ($class instanceof $parentClass) {
            throw new \InvalidArgumentException(sprintf('Class %s MUST implement class %s!', $class, $parentClass));
        }
    }

    /**
     * @param ProductInterface $product
     * @param LocaleInterface $locale
     * @param ChannelInterface $channel
     *
     * @return array
     */
    private function getAttributeDocuments(
        ProductInterface $product,
        LocaleInterface $locale,
        ChannelInterface $channel
    ): array {
        $productAttributes = $product->getAttributesByLocale(
            $locale->getCode(),
            $channel->getDefaultLocale()->getCode()
        );

        $attributeDocuments = [];
        foreach ($productAttributes as $syliusProductAttributeValue) {
            if (in_array($syliusProductAttributeValue->getCode(), $this->attributeWhitelist, true) || empty($this->attributeWhitelist)) {
                $attributeDocuments = array_merge(
                    $attributeDocuments,
                    $this->attributeDocumentFactory->create(
                        $syliusProductAttributeValue->getValue(),
                        $locale,
                        $syliusProductAttributeValue
                    )
                );
            }
        }

        return $attributeDocuments;
    }
}
