<?php

declare(strict_types=1);

namespace spec\Sylius\ElasticSearchPlugin\Document;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Sylius\ElasticSearchPlugin\Document\TaxonDocument;

final class TaxonDocumentSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(TaxonDocument::class);
    }

    function it_has_code()
    {
        $this->setCode('mug');

        $this->getCode()->shouldReturn('mug');
    }

    function it_has_slug()
    {
        $this->setSlug('/mug');

        $this->getSlug()->shouldReturn('/mug');
    }

    function it_has_images()
    {
        $images = new ArrayCollection();
        $this->setImages($images);

        $this->getImages()->shouldReturn($images);
    }

    function it_has_description()
    {
        $this->setDescription('Lorem ipsum');

        $this->getDescription()->shouldReturn('Lorem ipsum');
    }
}
