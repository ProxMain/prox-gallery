<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\DevelopmentSeed\Controllers;

use Prox\ProxGallery\Controllers\AbstractCliController;
use Prox\ProxGallery\Modules\DevelopmentSeed\Services\DevelopmentSeedService;

/**
 * WP-CLI command for generating development seed data.
 */
final class DevelopmentSeedCliController extends AbstractCliController
{
    public function __construct(private DevelopmentSeedService $service)
    {
    }

    protected static function moduleCommand(): string
    {
        return 'seed';
    }

    protected static function moduleDescription(): string
    {
        return 'Development seed commands for Prox Gallery.';
    }

    public function register(): void
    {
        $this->registerSubcommand(
            'import-random',
            [$this, 'importRandom'],
            [
                'shortdesc' => 'Imports random demo images, galleries, and category/gallery assignments.',
                'synopsis' => [
                    [
                        'type' => 'assoc',
                        'name' => 'images',
                        'description' => 'How many random images to generate.',
                        'optional' => true,
                        'default' => 100,
                    ],
                    [
                        'type' => 'assoc',
                        'name' => 'galleries',
                        'description' => 'How many galleries to create.',
                        'optional' => true,
                        'default' => 6,
                    ],
                    [
                        'type' => 'assoc',
                        'name' => 'max-categories',
                        'description' => 'Maximum categories to assign per image.',
                        'optional' => true,
                        'default' => 3,
                    ],
                    [
                        'type' => 'assoc',
                        'name' => 'max-galleries',
                        'description' => 'Maximum galleries to assign per image.',
                        'optional' => true,
                        'default' => 3,
                    ],
                    [
                        'type' => 'flag',
                        'name' => 'clear-existing',
                        'description' => 'Clears existing galleries and tracked queue before seeding.',
                        'optional' => true,
                    ],
                ],
            ]
        );
    }

    /**
     * @param list<string>         $args
     * @param array<string, mixed> $assocArgs
     */
    public function importRandom(array $args = [], array $assocArgs = []): void
    {
        $images = $this->intArg($assocArgs, 'images', 100, 1, 500);
        $galleries = $this->intArg($assocArgs, 'galleries', 6, 1, 100);
        $maxCategories = $this->intArg($assocArgs, 'max-categories', 3, 0, 20);
        $maxGalleries = $this->intArg($assocArgs, 'max-galleries', 3, 1, 20);
        $clearExisting = \WP_CLI\Utils\get_flag_value($assocArgs, 'clear-existing', false);

        $result = $this->service->importRandomData(
            $images,
            $galleries,
            $maxCategories,
            $maxGalleries,
            (bool) $clearExisting
        );

        \WP_CLI::success(
            sprintf(
                'Seed complete. Images created %d/%d, tracked %d, galleries created %d, gallery links %d, category links %d, failed images %d.',
                $result['created_images'],
                $result['requested_images'],
                $result['tracked_images'],
                $result['created_galleries'],
                $result['gallery_assignments'],
                $result['category_assignments'],
                $result['failed_images']
            )
        );

        if ($result['galleries'] !== []) {
            \WP_CLI\Utils\format_items('table', $result['galleries'], ['id', 'name', 'template']);
        }
    }

    /**
     * @param array<string, mixed> $assocArgs
     */
    private function intArg(array $assocArgs, string $name, int $default, int $min, ?int $max = null): int
    {
        if (! array_key_exists($name, $assocArgs)) {
            return $default;
        }

        $raw = $assocArgs[$name];
        $value = is_numeric($raw) ? (int) $raw : $default;
        $value = max($min, $value);

        if (is_int($max)) {
            $value = min($max, $value);
        }

        return $value;
    }
}
