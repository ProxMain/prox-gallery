<?php

declare(strict_types=1);

use Prox\ProxGallery\Models\GalleryModel;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\Services\FrontendGalleryService;
use Prox\ProxGallery\Services\TemplateCustomizationService;
use Prox\ProxGallery\States\FrontendGalleryState;
use Prox\ProxGallery\States\AdminConfigurationState;

final class FrontendGalleryServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_galleries');
        \delete_option('prox_gallery_options');
        \remove_all_filters('prox_gallery/frontend/can_render');
        \remove_all_filters('prox_gallery/frontend/templates');
        \remove_all_filters('prox_gallery/frontend/rendered_html');
    }

    protected function tearDown(): void
    {
        \remove_all_filters('prox_gallery/frontend/can_render');
        \remove_all_filters('prox_gallery/frontend/templates');
        \remove_all_filters('prox_gallery/frontend/rendered_html');
        \delete_option('prox_gallery_galleries');
        \delete_option('prox_gallery_options');
        parent::tearDown();
    }

    public function test_it_returns_empty_markup_when_no_galleries_exist(): void
    {
        $service = $this->service();

        self::assertSame('', $service->renderShortcode());
    }

    public function test_it_renders_basic_grid_template_by_default(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 10,
                    'name' => 'Nature',
                    'description' => 'Selected photos',
                    'image_ids' => [],
                ],
            ],
            false
        );

        $service = $this->service();
        $html = $service->renderShortcode(['id' => 10]);

        self::assertStringContainsString('prox-gallery--template-basic-grid', $html);
        self::assertStringContainsString('Nature', $html);
        self::assertStringContainsString('Selected photos', $html);
    }

    public function test_it_renders_masonry_template_when_requested(): void
    {
        \update_option(
            'prox_gallery_options',
            [
                'masonry_columns' => 5,
                'masonry_lightbox' => true,
                'masonry_hover_zoom' => true,
                'masonry_full_width' => true,
                'masonry_transition' => 'fade',
            ],
            false
        );
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 11,
                    'name' => 'Masonry test',
                    'description' => 'Layout',
                    'template' => 'masonry',
                    'image_ids' => [],
                ],
            ],
            false
        );

        $service = $this->service();
        $html = $service->renderShortcode(['id' => 11, 'template' => 'masonry']);

        self::assertStringContainsString('prox-gallery--template-masonry', $html);
        self::assertStringContainsString('prox-gallery--full-width', $html);
        self::assertStringContainsString('--prox-gallery-columns:5', $html);
    }

    public function test_it_uses_gallery_template_when_shortcode_template_is_not_provided(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 12,
                    'name' => 'Masonry from model',
                    'description' => '',
                    'template' => 'masonry',
                    'image_ids' => [],
                ],
            ],
            false
        );

        $service = $this->service();
        $html = $service->renderShortcode(['id' => 12]);

        self::assertStringContainsString('prox-gallery--template-masonry', $html);
    }

    public function test_it_allows_custom_template_registration_via_filter(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 20,
                    'name' => 'Urban',
                    'description' => '',
                    'image_ids' => [],
                ],
            ],
            false
        );

        \add_filter(
            'prox_gallery/frontend/templates',
            static function (array $templates): array {
                $templates['test-template'] = [
                    'label' => 'Test Template',
                    'is_pro' => false,
                    'render_callback' => static function (): string {
                        return '<div class="template-test">test-template-output</div>';
                    },
                ];

                return $templates;
            }
        );

        $service = $this->service();
        $html = $service->renderShortcode(
            [
                'id' => 20,
                'template' => 'test-template',
            ]
        );

        self::assertStringContainsString('template-test', $html);
        self::assertStringContainsString('test-template-output', $html);
    }

    public function test_it_respects_render_policy_filter(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 30,
                    'name' => 'Blocked',
                    'description' => '',
                    'image_ids' => [],
                ],
            ],
            false
        );
        \add_filter('prox_gallery/frontend/can_render', static fn (): bool => false);

        $service = $this->service();

        self::assertSame('', $service->renderShortcode(['id' => 30]));
    }

    public function test_it_falls_back_when_requested_template_is_not_available(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 31,
                    'name' => 'Fallback',
                    'description' => '',
                    'image_ids' => [],
                ],
            ],
            false
        );
        \add_filter(
            'prox_gallery/frontend/templates',
            static function (array $templates): array {
                $templates['pro-grid'] = [
                    'label' => 'Pro Grid',
                    'is_pro' => true,
                    'render_callback' => static fn (): string => '<div class="template-pro-grid">pro</div>',
                ];

                return $templates;
            }
        );

        $service = $this->service();
        $html = $service->renderShortcode(
            [
                'id' => 31,
                'template' => 'pro-grid',
            ]
        );

        self::assertStringContainsString('prox-gallery--template-basic-grid', $html);
        self::assertStringNotContainsString('template-pro-grid', $html);
    }

    public function test_it_allows_post_render_html_filtering(): void
    {
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 40,
                    'name' => 'Patched',
                    'description' => '',
                    'image_ids' => [],
                ],
            ],
            false
        );
        \add_filter(
            'prox_gallery/frontend/rendered_html',
            static fn (string $html): string => $html . '<!-- filtered -->'
        );

        $service = $this->service();
        $html = $service->renderShortcode(['id' => 40]);

        self::assertStringContainsString('<!-- filtered -->', $html);
    }

    public function test_it_applies_per_gallery_display_overrides_over_global_settings(): void
    {
        \update_option(
            'prox_gallery_options',
            [
                'basic_grid_columns' => 2,
                'basic_grid_lightbox' => true,
                'basic_grid_hover_zoom' => false,
                'basic_grid_full_width' => false,
            ],
            false
        );
        \update_option(
            'prox_gallery_galleries',
            [
                [
                    'id' => 44,
                    'name' => 'Overrides',
                    'description' => '',
                    'grid_columns_override' => 5,
                    'lightbox_override' => false,
                    'hover_zoom_override' => true,
                    'full_width_override' => true,
                    'image_ids' => [],
                ],
            ],
            false
        );

        $service = $this->service();
        $html = $service->renderShortcode(['id' => 44]);

        self::assertStringContainsString('--prox-gallery-columns:5', $html);
        self::assertStringContainsString('prox-gallery--hover-zoom', $html);
        self::assertStringNotContainsString('prox-gallery--lightbox-enabled', $html);
        self::assertStringContainsString('prox-gallery--full-width', $html);
    }

    public function test_it_includes_masonry_in_template_catalog(): void
    {
        $service = $this->service();
        $catalog = $service->templateCatalog();
        $slugs = array_values(
            array_map(
                static fn (array $item): string => (string) ($item['slug'] ?? ''),
                $catalog
            )
        );

        self::assertContains('basic-grid', $slugs);
        self::assertContains('masonry', $slugs);
    }

    private function service(): FrontendGalleryService
    {
        return new FrontendGalleryService(
            new FrontendGalleryState(),
            new FrontendVisibilityPolicy(),
            new GalleryModel(),
            new TemplateCustomizationService(new AdminConfigurationState())
        );
    }
}
