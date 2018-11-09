<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

use ONGR\ElasticsearchBundle\Annotation as ElasticSearch;

/**
 * @ElasticSearch\NestedType
 */
class AttributeDocument implements AttributeDocumentInterface
{
    /**
     * @var string
     *
     * @ElasticSearch\Property(type="keyword")
     */
    protected $code;

    /**
     * @var string
     *
     * @ElasticSearch\Property(
     *  type="text",
     *  name="name",
     *  options={
     *    "analyzer"="keywordAnalyzer",
     *    "fields"={
     *        "raw"={"type"="keyword"},
     *        "standard"={"type"="text", "analyzer"="incrementalAnalyzer"}
     *    }
     *  }
     * )
     */
    protected $name;

    /**
     * @var mixed
     *
     * @ElasticSearch\Property(
     *  type="text",
     *  name="value",
     *  options={
     *    "analyzer"="keywordAnalyzer",
     *    "fields"={
     *        "raw"={"type"="keyword"},
     *        "standard"={"type"="text", "analyzer"="incrementalAnalyzer"}
     *    }
     *  }
     * )
     */
    protected $value;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
