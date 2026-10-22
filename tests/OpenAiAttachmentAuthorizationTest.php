<?php

declare(strict_types=1);

use Prox\ProxGallery\Controllers\ForbiddenActionException;
use Prox\ProxGallery\Modules\OpenAi\Controllers\OpenAiActionController;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;
use Prox\ProxGallery\States\AdminConfigurationState;

/** Verifies the actual WordPress edit_post meta-capability mapping. */
final class OpenAiAttachmentAuthorizationTest extends WP_UnitTestCase
{
    private function controller(): OpenAiActionController
    {
        $settings = new OpenAiSettingsService(new AdminConfigurationState());
        return new OpenAiActionController($settings, new OpenAiStoryService($settings));
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    public function test_author_cannot_apply_to_another_authors_attachment(): void
    {
        $owner = self::factory()->user->create(['role' => 'author']);
        $attacker = self::factory()->user->create(['role' => 'author']);
        $id = self::factory()->attachment->create(['post_author' => $owner, 'post_mime_type' => 'image/jpeg']);
        wp_set_current_user($attacker);
        $this->expectException(ForbiddenActionException::class);
        $this->controller()->applyStory(['attachment_id' => $id, 'short_title' => 'Changed', 'story' => 'Changed'], 'apply');
    }

    public function test_author_cannot_generate_from_another_authors_attachment(): void
    {
        $owner = self::factory()->user->create(['role' => 'author']);
        $attacker = self::factory()->user->create(['role' => 'author']);
        $id = self::factory()->attachment->create(['post_author' => $owner, 'post_mime_type' => 'image/jpeg']);
        wp_set_current_user($attacker);
        $this->expectException(ForbiddenActionException::class);
        $this->controller()->generateStory(['attachment_id' => $id], 'generate');
    }

    public function test_author_can_apply_to_their_own_attachment(): void
    {
        $owner = self::factory()->user->create(['role' => 'author']);
        $id = self::factory()->attachment->create(['post_author' => $owner, 'post_mime_type' => 'image/jpeg']);
        wp_set_current_user($owner);
        $result = $this->controller()->applyStory(['attachment_id' => $id, 'short_title' => 'Quiet forest', 'story' => 'Morning light.'], 'apply');
        self::assertSame('Quiet forest', $result['short_title']);
        self::assertSame('Morning light.', get_post_field('post_content', $id));
    }
}
