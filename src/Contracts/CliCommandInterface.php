<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents a WP-CLI command registration.
 *
 * Commands should remain thin and delegate to application services.
 */
interface CliCommandInterface
{
    public static function command(): string;

    /**
     * @return array<string, mixed>
     */
    public static function args(): array;

    public function register(): void;
}