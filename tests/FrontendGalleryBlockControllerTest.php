<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\Admin\Services\TemplateCustomizationService;
use Prox\ProxGallery\Modules\Frontend\Controllers\FrontendGalleryBlockController;
use Prox\ProxGallery\Modules\Frontend\Controllers\FrontendGalleryController;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryRepository;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryTemplateRegistry;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryTemplateRenderer;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryPageProvisionerInterface;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\States\AdminConfigurationState;
use Prox\ProxGallery\States\FrontendGalleryState;

final class FrontendGalleryBlockControllerTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_galleries');
        \delete_option('prox_gallery_options');
    }

    public function test_it_renders_selected_gallery_through_frontend_shortcode_path(): void
    {
        $galleryCollection = new GalleryCollectionModel();
        $galleryService = new GalleryService($galleryCollection, $this->pageProvisioner());
        $gallery = $galleryService->create('Block Gallery');
        $frontendController = $this->frontendController($galleryCollection);
        $controller = new FrontendGalleryBlockController($frontendController, $galleryService);

        $html = $controller->renderBlock(['id' => (int) $gallery['id']]);

        self::assertStringContainsString('prox-gallery', $html);
        self::assertStringContainsString('Block Gallery', $html);
    }

    public function test_it_returns_empty_markup_without_a_valid_gallery_selection(): void
    {
        $galleryCollection = new GalleryCollectionModel();
        $galleryService = new GalleryService($galleryCollection, $this->pageProvisioner());
        $controller = new FrontendGalleryBlockController(
            $this->frontendController($galleryCollection),
            $galleryService
        );

        self::assertSame('', $controller->renderBlock(['id' => 0]));
    }

    private function frontendController(GalleryCollectionModel $galleryCollection): FrontendGalleryController
    {
        $templateSettings = new TemplateCustomizationService(new AdminConfigurationState());
        $renderer = new FrontendGalleryTemplateRenderer($templateSettings);
        $templateRegistry = new FrontendGalleryTemplateRegistry($renderer);
        $frontendService = new FrontendGalleryService(
            new FrontendGalleryState(),
            new FrontendVisibilityPolicy(),
            new FrontendGalleryRepository($galleryCollection),
            $renderer,
            $templateRegistry
        );

        return new FrontendGalleryController($frontendService, new FrontendTrackingService());
    }

    private function pageProvisioner(): GalleryPageProvisionerInterface
    {
        return new class () implements GalleryPageProvisionerInterface {
            public function provisionForGallery(int $galleryId, array $gallery): array
            {
                return [
                    'page_id' => 0,
                    'page_url' => '',
                    'menu_id' => 0,
                    'menu_item_id' => 0,
                ];
            }
        };
    }
}
