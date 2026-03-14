<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;

/**
 * Registers the Gutenberg block for selecting and rendering Prox galleries.
 */
final class FrontendGalleryBlockController implements ControllerInterface
{
    private const BLOCK_NAME = 'prox-gallery/gallery';
    private const EDITOR_SCRIPT_HANDLE = 'prox-gallery-block-editor';

    public function __construct(
        private FrontendGalleryController $frontendController,
        private GalleryService $galleryService
    ) {
    }

    public function id(): string
    {
        return 'frontend.gallery_block';
    }

    public function boot(): void
    {
        \add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock(): void
    {
        if (! \function_exists('register_block_type')) {
            return;
        }

        $this->registerEditorScript();

        \register_block_type(
            self::BLOCK_NAME,
            [
                'api_version' => 2,
                'editor_script' => self::EDITOR_SCRIPT_HANDLE,
                'render_callback' => [$this, 'renderBlock'],
                'attributes' => [
                    'id' => [
                        'type' => 'number',
                        'default' => 0,
                    ],
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderBlock(array $attributes = [], string $content = ''): string
    {
        $galleryId = isset($attributes['id']) ? (int) $attributes['id'] : 0;

        if ($galleryId <= 0) {
            return '';
        }

        return $this->frontendController->renderShortcode(
            [
                'id' => $galleryId,
            ],
            $content,
            self::BLOCK_NAME
        );
    }

    private function registerEditorScript(): void
    {
        $relativePath = 'assets/editor/prox-gallery-block.js';
        $absolutePath = \trailingslashit(\PROX_GALLERY_DIR) . $relativePath;
        $version = \is_readable($absolutePath)
            ? (string) \filemtime($absolutePath)
            : '1.0.0';

        \wp_register_script(
            self::EDITOR_SCRIPT_HANDLE,
            \plugins_url($relativePath, \PROX_GALLERY_FILE),
            [
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'wp-i18n',
            ],
            $version,
            true
        );

        \wp_add_inline_script(
            self::EDITOR_SCRIPT_HANDLE,
            sprintf(
                'window.ProxGalleryBlockEditor = %s;',
                \wp_json_encode(
                    [
                        'galleries' => $this->galleryCatalog(),
                    ]
                )
            ),
            'before'
        );
    }

    /**
     * @return list<array{id:number, name:string}>
     */
    private function galleryCatalog(): array
    {
        $catalog = [];

        foreach ($this->galleryService->list() as $gallery) {
            $galleryId = isset($gallery['id']) ? (int) $gallery['id'] : 0;
            $galleryName = isset($gallery['name']) ? trim((string) $gallery['name']) : '';

            if ($galleryId <= 0 || $galleryName === '') {
                continue;
            }

            $catalog[] = [
                'id' => $galleryId,
                'name' => $galleryName,
            ];
        }

        return $catalog;
    }
}
