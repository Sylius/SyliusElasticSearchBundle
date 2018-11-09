<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

interface AttributeDocumentInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @param string $code
     */
    public function setCode(string $code): void;

    /**
     * @return string
     */
    public function getName(): ?string;

    /**
     * @param string $name
     */
    public function setName(?string $name): void;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value): void;
}
