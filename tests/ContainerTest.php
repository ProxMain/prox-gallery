<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\Container;

final class ContainerTest extends WP_UnitTestCase
{
    public function test_it_returns_the_same_instance_per_id_until_rebound(): void
    {
        $container = new Container();
        $container->set('demo', static fn (): stdClass => new stdClass());

        $first = $container->get('demo');
        $second = $container->get('demo');
        self::assertSame($first, $second);

        $container->set('demo', static fn (): stdClass => new stdClass());
        $third = $container->get('demo');
        self::assertNotSame($first, $third);
    }

    public function test_it_throws_for_unknown_ids(): void
    {
        $container = new Container();

        $this->expectException(RuntimeException::class);
        $container->get('missing');
    }
}
