<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;

final class CoreModuleBootTest extends WP_UnitTestCase
{
	public function test_it_boots_the_core_module_via_the_application_lifecycle(): void
	{
		$before = \did_action('prox_gallery/module/core/booted');

		App::make()->boot();

		self::assertSame($before + 1, \did_action('prox_gallery/module/core/booted'));
	}
}
