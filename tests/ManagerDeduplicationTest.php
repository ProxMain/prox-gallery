<?php

declare(strict_types=1);

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Contracts\ModuleInterface;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\ModuleManager;

final class ManagerDeduplicationTest extends WP_UnitTestCase
{
    public function test_module_manager_replaces_existing_module_with_same_id(): void
    {
        $state = (object) ['booted' => []];
        $manager = new ModuleManager();
        $manager->add($this->createModule('duplicate', 'first', $state));
        $manager->add($this->createModule('duplicate', 'second', $state));

        $manager->boot();

        self::assertSame(['second'], $state->booted);
    }

    public function test_controller_manager_replaces_existing_controller_with_same_id(): void
    {
        $state = (object) ['booted' => []];
        $manager = new ControllerManager();
        $manager->add($this->createController('duplicate', 'first', $state));
        $manager->add($this->createController('duplicate', 'second', $state));

        $manager->boot();

        self::assertSame(['second'], $state->booted);
    }

    private function createModule(string $id, string $marker, object $state): ModuleInterface
    {
        return new class($id, $marker, $state) implements ModuleInterface {
            public function __construct(
                private string $id,
                private string $marker,
                private object $state
            ) {}

            public function id(): string
            {
                return $this->id;
            }

            public function boot(): void
            {
                $this->state->booted[] = $this->marker;
            }
        };
    }

    private function createController(string $id, string $marker, object $state): ControllerInterface
    {
        return new class($id, $marker, $state) implements ControllerInterface {
            public function __construct(
                private string $id,
                private string $marker,
                private object $state
            ) {}

            public function id(): string
            {
                return $this->id;
            }

            public function boot(): void
            {
                $this->state->booted[] = $this->marker;
            }
        };
    }
}
