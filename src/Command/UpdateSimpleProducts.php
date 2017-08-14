<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Command;

use ONGR\ElasticsearchBundle\Service\Manager;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\ElasticSearchPlugin\Factory\ProductDocumentFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateSimpleProducts extends Command
{
    /**
     * @var ProductRepositoryInterface
     */
    private $syliusProductRepository;

    /**
     * @var ChannelRepositoryInterface
     */
    private $syliusChannelRepository;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ProductDocumentFactoryInterface
     */
    private $productDocumentFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductRepositoryInterface $syliusProductRepository
     * @param ChannelRepositoryInterface $syliusChannelRepository
     * @param Manager $manager
     * @param ProductDocumentFactoryInterface $productDocumentFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ProductRepositoryInterface $syliusProductRepository, ChannelRepositoryInterface $syliusChannelRepository, Manager $manager, ProductDocumentFactoryInterface $productDocumentFactory, LoggerInterface $logger)
    {
        $this->syliusProductRepository = $syliusProductRepository;
        $this->syliusChannelRepository = $syliusChannelRepository;
        $this->manager = $manager;
        $this->productDocumentFactory = $productDocumentFactory;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sylius:elastic-search:update-simple-product')
            ->setDescription('Update simple products in elastic search index. Warning it will drop whole index')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, null, 100)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');

        $this->manager->dropAndCreateIndex();

        /** @var Channelinterface[] $channels */
        $channels = $this->syliusChannelRepository->findAll();

        foreach ($channels as $channel) {
            $locales = $channel->getLocales();

            foreach ($locales as $locale) {
                $this->createProducts(
                    $channel,
                    $locale,
                    $limit,
                    $this->countProductsInChannelAndLocale($channel, $locale),
                    $output
                );
            }
        }
    }

    /**
     * @param ChannelInterface $channel
     * @param LocaleInterface $locale
     * @param int $fetchLimit
     * @param int $howManyProductsToCreate
     * @param OutputInterface $output
     */
    private function createProducts(
        ChannelInterface $channel,
        LocaleInterface $locale,
        $fetchLimit,
        $howManyProductsToCreate,
        OutputInterface $output
    ) {
        $progress = new ProgressBar($output, $howManyProductsToCreate);
        $progress->start();
        $iterations = ceil($howManyProductsToCreate / $fetchLimit);
        $lastPersistedProductId = 0;

        for ($i = 0; $i < $iterations; $i++) {
            /** @var ProductInterface[] $syliusProducts */
            $syliusProducts = $this->syliusProductRepository
                ->createListQueryBuilder($locale->getCode())
                ->andWhere('o.id > :lastPersistedProductId')
                ->andWhere(':channel MEMBER OF o.channels')
                ->setParameter('lastPersistedProductId', $lastPersistedProductId)
                ->setParameter('channel', $channel)
                ->setMaxResults($fetchLimit)
                ->addOrderBy('o.id', 'ASC')
                ->getQuery()
                ->getResult()
            ;

            foreach ($syliusProducts as $syliusProduct) {
                $progress->advance();

                try {
                    $productDocument = $this->productDocumentFactory->createFromSyliusSimpleProductModel(
                        $syliusProduct,
                        $locale,
                        $channel
                    );

                    $this->manager->persist($productDocument);
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }

                $lastPersistedProductId = $syliusProduct->getId();
            }

            $this->manager->commit();
        }

        $progress->finish();
    }

    /**
     * @param ChannelInterface $channel
     * @param LocaleInterface $locale
     *
     * @return int
     */
    private function countProductsInChannelAndLocale(ChannelInterface $channel, LocaleInterface $locale)
    {
        return
            $this->syliusProductRepository
                ->createListQueryBuilder($locale->getCode())
                ->select('COUNT(o.id)')
                ->andWhere(':channel MEMBER OF o.channels')
                ->setParameter('channel', $channel)
                ->getQuery()
                ->getSingleScalarResult()
            ;
    }
}
