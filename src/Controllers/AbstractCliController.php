<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\CliCommandInterface;

/**
 * Base class for WP-CLI controllers with a shared command namespace.
 */
abstract class AbstractCliController implements CliCommandInterface
{
    public static function command(): string
    {
        return static::commandNamespace() . ' ' . static::moduleCommand();
    }

    /**
     * @return array<string, mixed>
     */
    public static function args(): array
    {
        return [
            'shortdesc' => static::moduleDescription(),
        ];
    }

    abstract public function register(): void;

    protected static function commandNamespace(): string
    {
        return 'prox';
    }

    abstract protected static function moduleCommand(): string;

    abstract protected static function moduleDescription(): string;

    /**
     * @param callable(mixed...): mixed|array{0:object|string,1:string} $callable
     * @param array<string, mixed>                                       $arguments
     */
    protected function registerSubcommand(string $subcommand, $callable, array $arguments = []): void
    {
        \WP_CLI::add_command(static::command() . ' ' . $subcommand, $callable, $arguments);
    }
}
