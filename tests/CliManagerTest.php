<?php

declare(strict_types=1);

use Prox\ProxGallery\Contracts\CliCommandInterface;
use Prox\ProxGallery\Managers\CliManager;

final class CliManagerTest extends WP_UnitTestCase
{
    public function test_it_does_not_register_commands_when_wp_cli_is_not_enabled(): void
    {
        if (defined('WP_CLI') && \WP_CLI) {
            $this->markTestSkipped('WP_CLI is enabled in this runtime.');
        }

        $state = (object) ['registerCalls' => 0];
        $manager = new CliManager();
        $manager->add($this->createCommand($state));

        $manager->boot();

        self::assertSame(0, $state->registerCalls);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_registers_commands_when_wp_cli_is_enabled(): void
    {
        if (! defined('WP_CLI')) {
            define('WP_CLI', true);
        }

        if (! \WP_CLI) {
            $this->markTestSkipped('WP_CLI constant is defined as false.');
        }

        $state = (object) ['registerCalls' => 0];
        $manager = new CliManager();
        $manager->add($this->createCommand($state));

        $manager->boot();

        self::assertSame(1, $state->registerCalls);
    }

    private function createCommand(object $state): CliCommandInterface
    {
        return new class($state) implements CliCommandInterface {
            public function __construct(private object $state) {}

            public static function command(): string
            {
                return 'prox-gallery:test';
            }

            /**
             * @return array<string, mixed>
             */
            public static function args(): array
            {
                return [];
            }

            public function register(): void
            {
                $this->state->registerCalls++;
            }
        };
    }
}
