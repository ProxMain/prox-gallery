<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryModel;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryRepositoryInterface;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryTemplateRegistryInterface;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryTemplateRendererInterface;
use Prox\ProxGallery\States\FrontendGalleryState;

/**
 * Frontend gallery orchestration service.
 */
final class FrontendGalleryService implements ServiceInterface
{
    public function __construct(
        private FrontendGalleryState $state,
        private FrontendVisibilityPolicy $policy,
        private GalleryModel $model,
        private FrontendGalleryRepositoryInterface $repository,
        private FrontendGalleryTemplateRendererInterface $renderer,
        private FrontendGalleryTemplateRegistryInterface $templateRegistry
    ) {
    }

    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        /**
         * Fires after the frontend gallery service boots.
         *
         * @param FrontendGalleryState     $state  Frontend state instance.
         * @param FrontendVisibilityPolicy $policy Visibility policy instance.
         * @param GalleryModel             $model  Gallery model instance.
         */
        \do_action(
            'prox_gallery/service/frontend_gallery/booted',
            $this->state,
            $this->policy,
            $this->model
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderShortcode(array $attributes = []): string
    {
        if (! (bool) \apply_filters('prox_gallery/frontend/can_render', true)) {
            return '';
        }

        $galleryId = isset($attributes['id']) ? (int) $attributes['id'] : 0;
        $requestedTemplate = isset($attributes['template']) ? (string) $attributes['template'] : '';
        $galleries = $this->repository->loadGalleries($galleryId);

        if ($galleries === []) {
            return '';
        }

        if ($requestedTemplate === '' && $galleryId > 0 && count($galleries) === 1) {
            $galleryTemplate = isset($galleries[0]['template']) ? (string) $galleries[0]['template'] : '';

            if ($galleryTemplate !== '') {
                $requestedTemplate = $galleryTemplate;
            }
        }

        $templates = $this->templates();
        $templateSlug = $this->templateRegistry->resolveTemplateSlug($requestedTemplate, $templates, $this);
        $template = $templates[$templateSlug] ?? [];
        $callback = $template['render_callback'] ?? null;

        if (! is_callable($callback)) {
            return '';
        }

        $html = (string) \call_user_func($callback, $galleries, $attributes, $templateSlug, $this);

        return (string) \apply_filters(
            'prox_gallery/frontend/rendered_html',
            $html,
            $galleries,
            $templateSlug,
            $attributes,
            $this
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templates(): array
    {
        return $this->templateRegistry->templates($this);
    }

    /**
     * @return list<array{slug:string, label:string, is_pro:bool, available:bool}>
     */
    public function templateCatalog(): array
    {
        return $this->templateRegistry->templateCatalog($this);
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderBasicGridTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        return $this->renderer->renderBasicGridTemplate($galleries, $attributes, $templateSlug);
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderMasonryTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        return $this->renderer->renderMasonryTemplate($galleries, $attributes, $templateSlug);
    }
}
