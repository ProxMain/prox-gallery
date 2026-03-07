<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Services;

use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryTemplateRegistryInterface;

/**
 * Builds template definitions and resolves template selection rules.
 */
final class FrontendGalleryTemplateRegistry implements FrontendGalleryTemplateRegistryInterface
{
    public function __construct(private FrontendGalleryTemplateRenderer $renderer)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templates(FrontendGalleryService $service): array
    {
        $templates = [
            'basic-grid' => [
                'label' => 'Basic Grid',
                'is_pro' => false,
                'render_callback' => [$this->renderer, 'renderBasicGridTemplate'],
            ],
            'masonry' => [
                'label' => 'Masonry',
                'is_pro' => false,
                'render_callback' => [$this->renderer, 'renderMasonryTemplate'],
            ],
        ];

        $filtered = \apply_filters('prox_gallery/frontend/templates', $templates, $service);

        return is_array($filtered) ? $filtered : $templates;
    }

    /**
     * @param array<string, array<string, mixed>> $templates
     */
    public function resolveTemplateSlug(string $requestedTemplate, array $templates, FrontendGalleryService $service): string
    {
        if (
            $requestedTemplate !== ''
            && isset($templates[$requestedTemplate])
            && is_array($templates[$requestedTemplate])
            && $this->templateIsAvailable($requestedTemplate, $templates[$requestedTemplate], $service)
        ) {
            return $requestedTemplate;
        }

        $keys = array_keys($templates);

        foreach ($keys as $key) {
            if (
                is_string($key)
                && $key !== ''
                && isset($templates[$key])
                && is_array($templates[$key])
                && $this->templateIsAvailable($key, $templates[$key], $service)
            ) {
                return $key;
            }
        }

        return 'basic-grid';
    }

    /**
     * @return list<array{slug:string, label:string, is_pro:bool, available:bool}>
     */
    public function templateCatalog(FrontendGalleryService $service): array
    {
        $catalog = [];

        foreach ($this->templates($service) as $slug => $template) {
            if (! is_string($slug) || $slug === '' || ! is_array($template)) {
                continue;
            }

            $catalog[] = [
                'slug' => $slug,
                'label' => isset($template['label']) ? (string) $template['label'] : $slug,
                'is_pro' => $this->templateIsPro($template),
                'available' => $this->templateIsAvailable($slug, $template, $service),
            ];
        }

        $filtered = \apply_filters('prox_gallery/frontend/template_catalog', $catalog, $service);

        return is_array($filtered) ? $filtered : $catalog;
    }

    /**
     * @param array<string, mixed> $template
     */
    private function templateIsPro(array $template): bool
    {
        return isset($template['is_pro']) && (bool) $template['is_pro'];
    }

    /**
     * @param array<string, mixed> $template
     */
    private function templateIsAvailable(string $slug, array $template, FrontendGalleryService $service): bool
    {
        $default = ! $this->templateIsPro($template);
        $allowed = \apply_filters(
            'prox_gallery/frontend/template_is_available',
            $default,
            $slug,
            $template,
            $service
        );

        return (bool) $allowed;
    }
}
