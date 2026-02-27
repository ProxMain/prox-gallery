<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Managers;

use Prox\ProxGallery\Contracts\CliCommandInterface;

/**
 * Registers WP-CLI commands when WP-CLI is available.
 */
final class CliManager extends AbstractManager
{
    /**
     * @var list<CliCommandInterface>
     */
    private array $commands = [];

    public function id(): string
    {
        return 'cli';
    }

    public function add(CliCommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    protected function register(): void
    {
        if (! defined('WP_CLI') || ! \WP_CLI) {
            return;
        }

        foreach ($this->commands as $command) {
            $command->register();
        }
    }
}