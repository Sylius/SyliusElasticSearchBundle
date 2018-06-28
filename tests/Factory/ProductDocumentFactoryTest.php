<?php

declare(strict_types=1);

namespace Tests\Sylius\ElasticSearchPlugin\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Bundle\ChannelBundle\Doctrine\ORM\ChannelRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\ElasticSearchPlugin\Document\AttributeDocument;
use Sylius\ElasticSearchPlugin\Document\ImageDocument;
use Sylius\ElasticSearchPlugin\Document\OptionDocument;
use Sylius\ElasticSearchPlugin\Document\PriceDocument;
use Sylius\ElasticSearchPlugin\Document\ProductDocument;
use Sylius\ElasticSearchPlugin\Document\TaxonDocument;
use Sylius\ElasticSearchPlugin\Document\VariantDocument;
use Sylius\ElasticSearchPlugin\Factory\Document\AttributeDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\ImageDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\OptionDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\PriceDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\ProductDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\TaxonDocumentFactory;
use Sylius\ElasticSearchPlugin\Factory\Document\VariantDocumentFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductDocumentFactoryTest extends KernelTestCase
{
    /** @var ProductRepository */
    protected $productRepository;

    /** @var RepositoryInterface */
    protected $localeRepository;

    /** @var ChannelRepository */
    protected $channelRepository;

    public function setUp()
    {
        self::bootKernel();

        $this->productRepository = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')->getRepository(
            Product::class
        )
        ;
        $this->localeRepository = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')->getRepository(
            Locale::class
        )
        ;
        $this->channelRepository = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')->getRepository(
            Channel::class
        )
        ;
    }

    /**
     * @test
     */
    public function it_creates_product_document_from_sylius_product_model()
    {
        /** @var ChannelInterface $syliusChannel */
        $syliusChannel = $this->channelRepository->findOneByCode('WEB_GB');
        /** @var Locale $syliusLocale */
        $syliusLocale = $this->localeRepository->findOneBy(['code' => 'en_GB']);
        $createdAt = \DateTime::createFromFormat(\DateTime::W3C, '2017-04-18T16:12:55+02:00');

        /** @var Product $syliusProduct */
        $syliusProduct = $this->productRepository->findOneByChannelAndSlug(
            $syliusChannel,
            $syliusLocale->getCode(),
            'logan-mug'
        );
        $syliusProduct->setCreatedAt($createdAt);

        $factory = new ProductDocumentFactory(
            ProductDocument::class,
            new AttributeDocumentFactory(AttributeDocument::class),
            new ImageDocumentFactory(ImageDocument::class),
            new PriceDocumentFactory(PriceDocument::class),
            new TaxonDocumentFactory(TaxonDocument::class, new ImageDocumentFactory(ImageDocument::class)),
            new VariantDocumentFactory(
                VariantDocument::class,
                new PriceDocumentFactory(PriceDocument::class),
                new ImageDocumentFactory(ImageDocument::class),
                new OptionDocumentFactory(OptionDocument::class)
            ),
            ['MUG_COLLECTION_CODE', 'MUG_MATERIAL_CODE', 'PRODUCTION_YEAR']
        );
        /** @var ProductDocument $product */
        $product = $factory->create(
            $syliusProduct,
            $syliusLocale,
            $syliusChannel
        );

        $taxon = $this->makeMainTaxon();

        $productTaxons = $this->makeProductTaxons();

        $productAttributes = $this->makeProductAttributes();

        $this->assertEquals($product->getCode(), $product->getCode());
        $this->assertEquals($product->getName(), $product->getName());
        $this->assertEquals('en_GB', $product->getLocaleCode());
        $this->assertEquals(new ArrayCollection($productAttributes), $product->getAttributes());
        $this->assertEquals(1000, $product->getPrice()->getAmount());
        $this->assertEquals('GBP', $product->getPrice()->getCurrency());
        $this->assertEquals('en_GB', $product->getLocaleCode());
        $this->assertEquals('WEB_GB', $product->getChannelCode());
        $this->assertEquals('logan-mug', $product->getSlug());
        $this->assertEquals('Logan Mug', $product->getName());
        $this->assertEquals($createdAt, $product->getCreatedAt());
        $this->assertEquals('Logan Mug', $product->getDescription());
        $this->assertEquals($taxon->getCode(), $product->getMainTaxon()->getCode());
        $this->assertEquals(new ArrayCollection($productTaxons), $product->getTaxons());
        $this->assertEquals(0.0, $product->getAverageReviewRating());
    }

    /**
     * @test
     */
    public function it_creates_product_document_only_with_whitelisted_attributes()
    {
        /** @var ChannelInterface $syliusChannel */
        $syliusChannel = $this->channelRepository->findOneByCode('WEB_GB');
        /** @var Locale $syliusLocale */
        $syliusLocale = $this->localeRepository->findOneBy(['code' => 'en_GB']);
        $createdAt = \DateTime::createFromFormat(\DateTime::W3C, '2017-04-18T16:12:55+02:00');

        /** @var Product $syliusProduct */
        $syliusProduct = $this->productRepository->findOneByChannelAndSlug(
            $syliusChannel,
            $syliusLocale->getCode(),
            'logan-mug'
        );
        $syliusProduct->setCreatedAt($createdAt);

        $factory = new ProductDocumentFactory(
            ProductDocument::class,
            new AttributeDocumentFactory(AttributeDocument::class),
            new ImageDocumentFactory(ImageDocument::class),
            new PriceDocumentFactory(PriceDocument::class),
            new TaxonDocumentFactory(TaxonDocument::class, new ImageDocumentFactory(ImageDocument::class)),
            new VariantDocumentFactory(
                VariantDocument::class,
                new PriceDocumentFactory(PriceDocument::class),
                new ImageDocumentFactory(ImageDocument::class),
                new OptionDocumentFactory(OptionDocument::class)
            ),
            ['PRODUCTION_YEAR']
        );
        /** @var ProductDocument $product */
        $product = $factory->create(
            $syliusProduct,
            $syliusLocale,
            $syliusChannel
        );

        $taxon = $this->makeMainTaxon();

        $productTaxons = $this->makeProductTaxons();

        $productAttribute = $this->makeProductionYearAttribute();

        $this->assertEquals($product->getCode(), $product->getCode());
        $this->assertEquals($product->getName(), $product->getName());
        $this->assertEquals('en_GB', $product->getLocaleCode());
        $this->assertEquals(
            new ArrayCollection([$productAttribute]),
            $product->getAttributes()
        );
        $this->assertEquals(1000, $product->getPrice()->getAmount());
        $this->assertEquals('GBP', $product->getPrice()->getCurrency());
        $this->assertEquals('en_GB', $product->getLocaleCode());
        $this->assertEquals('WEB_GB', $product->getChannelCode());
        $this->assertEquals('logan-mug', $product->getSlug());
        $this->assertEquals('Logan Mug', $product->getName());
        $this->assertEquals($createdAt, $product->getCreatedAt());
        $this->assertEquals('Logan Mug', $product->getDescription());
        $this->assertEquals($taxon, $product->getMainTaxon());
        $this->assertEquals(new ArrayCollection($productTaxons), $product->getTaxons());
        $this->assertEquals(0.0, $product->getAverageReviewRating());
    }

    /**
     * @return TaxonDocument
     */
    private function makeMainTaxon(): TaxonDocument
    {
        $taxon = new TaxonDocument();
        $taxon->setCode('MUG');
        $taxon->setPosition(0);
        $taxon->setSlug('categories/mugs');
        $taxon->setDescription('Lorem ipsum');

        return $taxon;
    }

    /**
     * @return array
     */
    private function makeProductTaxons(): array
    {
        $productTaxons = [];
        $productTaxon = new TaxonDocument();
        $productTaxon->setCode('MUG');
        $productTaxon->setSlug('categories/mugs');
        $productTaxon->setPosition(0);
        $productTaxon->setDescription('Lorem ipsum');
        $productTaxons[] = $productTaxon;
        $productTaxon = new TaxonDocument();
        $productTaxon->setCode('BRAND');
        $productTaxon->setSlug('brands');
        $productTaxon->setPosition(3);
        $productTaxon->setDescription('Lorem ipsum');
        $productTaxons[] = $productTaxon;

        return $productTaxons;
    }

    /**
     * @return AttributeDocument
     */
    private function makeProductionYearAttribute(): AttributeDocument
    {
        $productAttribute = new AttributeDocument();
        $productAttribute->setCode('PRODUCTION_YEAR');
        $productAttribute->setName('Production year');
        $productAttribute->setValue('2015');

        return $productAttribute;
    }

    private function makeProductAttributes(): array
    {
        $productAttributes = [];

        $productAttribute = new AttributeDocument();
        $productAttribute->setCode('MUG_COLLECTION_CODE');
        $productAttribute->setName('Mug collection');
        $productAttribute->setValue('HOLIDAY COLLECTION');
        $productAttributes[] = $productAttribute;

        $productAttribute = new AttributeDocument();
        $productAttribute->setCode('MUG_MATERIAL_CODE');
        $productAttribute->setName('Mug material');
        $productAttribute->setValue('Wood');
        $productAttributes[] = $productAttribute;

        $productAttributes[] = $this->makeProductionYearAttribute();

        return $productAttributes;
    }
}
