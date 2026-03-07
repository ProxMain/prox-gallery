<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Contracts;

use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryService;

/**
 * Builds and resolves frontend template definitions.
 */
interface FrontendGalleryTemplateRegistryInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function templates(FrontendGalleryService $service): array;

    /**
     * @param array<string, array<string, mixed>> $templates
     */
    public function resolveTemplateSlug(string $requestedTemplate, array $templates, FrontendGalleryService $service): string;

    /**
     * @return list<array{slug:string, label:string, is_pro:bool, available:bool}>
     */
    public function templateCatalog(FrontendGalleryService $service): array;
}
