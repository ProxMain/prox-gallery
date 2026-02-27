<?php

declare(strict_types=1);

use Prox\ProxGallery\Managers\AbstractManager;

final class AbstractManagerBootTest extends WP_UnitTestCase
{
    public function test_it_registers_only_once_even_when_boot_is_called_multiple_times(): void
    {
        $manager = new class() extends AbstractManager {
            public int $registerCalls = 0;

            public function id(): string
            {
                return 'test';
            }

            protected function register(): void
            {
                $this->registerCalls++;
            }
        };

        $manager->boot();
        $manager->boot();

        self::assertSame(1, $manager->registerCalls);
    }
}
