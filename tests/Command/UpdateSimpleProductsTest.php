<?php

declare(strict_types=1);

namespace Tests\Sylius\ElasticSearchPlugin\Command;

use Lakion\ApiTestCase\JsonApiTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;


final class UpdateSimpleProductsTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_reindex_elastic_search_from_default_shop_configuration()
    {
        $this->loadFixturesFromFile('shop.yml');
        $this->executeCommand();
        $this->assertElasticSearchDocumentCount(5);
    }

    /**
     * @test
     */
    public function it_reindex_elastic_search()
    {
        $this->loadFixturesFromFile('shop_with_different_prices.yml');
        $this->executeCommand();
        $this->assertElasticSearchDocumentCount(3);
    }

    private function executeCommand()
    {
        $application = new Application(static::$sharedKernel);
        $application->add(static::$sharedKernel->getContainer()->get('sylius_elastic_search.command.update_product'));
        $command = $application->find('sylius:elastic-search:update-simple-product');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
    }

    /**
     * @param int $expected
     */
    private function assertElasticSearchDocumentCount($expected)
    {
        $productRepository = static::$sharedKernel->getContainer()->get('es.manager.default.product');

        $this->assertEquals($expected, $productRepository->count($productRepository->createSearch()));
    }

    /**
     * @before
     */
    protected function purgeElasticSearch()
    {
        $elasticSearchManager = static::$sharedKernel->getContainer()->get('es.manager.default');
        $elasticSearchManager->dropAndCreateIndex();
    }
}
