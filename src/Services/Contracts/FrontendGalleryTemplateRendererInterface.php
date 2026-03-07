<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services\Contracts;

/**
 * Renders frontend gallery templates.
 */
interface FrontendGalleryTemplateRendererInterface
{
    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderBasicGridTemplate(array $galleries, array $attributes, string $templateSlug): string;

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderMasonryTemplate(array $galleries, array $attributes, string $templateSlug): string;
}
