<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Document;

interface ImageDocumentInterface
{
    /**
     * @return string
     */
    public function getCode(): ?string;

    /**
     * @param string $code
     */
    public function setCode(?string $code): void;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path
     */
    public function setPath(string $path): void;
}
