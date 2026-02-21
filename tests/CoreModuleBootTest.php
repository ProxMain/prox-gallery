<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;

final class CoreModuleBootTest extends WP_UnitTestCase
{
	public function test_it_boots_the_core_module_via_the_application_lifecycle(): void
	{
		$booted = false;

		add_action(
			'prox_gallery/module/core/booted',
			static function () use (&$booted): void {
				$booted = true;
			}
		);

		App::make()->boot();

		self::assertTrue($booted);
	}
}