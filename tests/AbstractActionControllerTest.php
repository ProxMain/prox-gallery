<?php

declare(strict_types=1);

use Prox\ProxGallery\Controllers\AbstractActionController;

final class AbstractActionControllerTest extends WP_UnitTestCase
{
    private TestableAbstractActionController $controller;
    private int $adminUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new TestableAbstractActionController();
        $this->controller->boot();
        $this->adminUserId = self::factory()->user->create(['role' => 'administrator']);

        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];

        \wp_set_current_user(0);

        parent::tearDown();
    }

    public function test_it_returns_bad_request_for_invalid_action_payloads(): void
    {
        \wp_set_current_user($this->adminUserId);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_invalid',
                '_ajax_nonce' => \wp_create_nonce('prox_gallery_test_invalid'),
            ]
        );

        self::assertFalse($response['success']);
        self::assertSame(400, $response['status']);
        self::assertSame('Invalid payload.', $response['data']['message']);
    }

    public function test_it_returns_internal_server_error_for_unexpected_failures(): void
    {
        \wp_set_current_user($this->adminUserId);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_failing',
                '_ajax_nonce' => \wp_create_nonce('prox_gallery_test_failing'),
            ]
        );

        self::assertFalse($response['success']);
        self::assertSame(500, $response['status']);
        self::assertSame('Request failed.', $response['data']['message']);
    }

    public function test_it_returns_forbidden_for_capability_failures(): void
    {
        \wp_set_current_user(0);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_success',
                '_ajax_nonce' => \wp_create_nonce('prox_gallery_test_success'),
            ]
        );

        self::assertFalse($response['success']);
        self::assertSame(403, $response['status']);
        self::assertSame('You are not allowed to perform this action.', $response['data']['message']);
    }

    public function test_it_returns_forbidden_for_nonce_failures(): void
    {
        \wp_set_current_user($this->adminUserId);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_success',
                '_ajax_nonce' => 'invalid-nonce',
            ]
        );

        self::assertFalse($response['success']);
        self::assertSame(403, $response['status']);
        self::assertSame('Nonce verification failed.', $response['data']['message']);
    }

    public function test_it_returns_not_found_for_unknown_actions(): void
    {
        \wp_set_current_user($this->adminUserId);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_missing',
                '_ajax_nonce' => \wp_create_nonce('prox_gallery_test_missing'),
            ]
        );

        self::assertFalse($response['success']);
        self::assertSame(404, $response['status']);
        self::assertSame('Unknown action.', $response['data']['message']);
    }

    public function test_it_returns_success_payload_for_valid_requests(): void
    {
        \wp_set_current_user($this->adminUserId);

        $response = $this->dispatch(
            [
                'action' => 'prox_gallery_test_success',
                '_ajax_nonce' => \wp_create_nonce('prox_gallery_test_success'),
                'name' => 'gallery',
            ]
        );

        self::assertTrue($response['success']);
        self::assertSame(200, $response['status']);
        self::assertSame('prox_gallery_test_success', $response['data']['action']);
        self::assertSame('gallery', $response['data']['name']);
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array{success:bool, status:int, data:array<string, mixed>}
     */
    private function dispatch(array $request): array
    {
        $_POST = $request;
        $_REQUEST = $request;

        $this->controller->handleAjaxRequest();

        return $this->controller->lastResponse();
    }
}

final class TestableAbstractActionController extends AbstractActionController
{
    /**
     * @var array{success:bool, status:int, data:array<string, mixed>}
     */
    private array $lastResponse = [
        'success' => false,
        'status' => 0,
        'data' => [],
    ];

    public function id(): string
    {
        return 'test.actions';
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            'prox_gallery_test_success' => [
                'callback' => 'successfulAction',
                'nonce_action' => 'prox_gallery_test_success',
                'capability' => 'manage_options',
            ],
            'prox_gallery_test_invalid' => [
                'callback' => 'invalidAction',
                'nonce_action' => 'prox_gallery_test_invalid',
                'capability' => 'manage_options',
            ],
            'prox_gallery_test_failing' => [
                'callback' => 'failingAction',
                'nonce_action' => 'prox_gallery_test_failing',
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function successfulAction(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'name' => isset($payload['name']) ? (string) $payload['name'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function invalidAction(array $payload, string $action): array
    {
        throw new \InvalidArgumentException('Invalid payload.');
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function failingAction(array $payload, string $action): array
    {
        throw new \LogicException('Unexpected failure.');
    }

    /**
     * @return array{success:bool, status:int, data:array<string, mixed>}
     */
    public function lastResponse(): array
    {
        return $this->lastResponse;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function sendSuccess(array $payload): void
    {
        $this->lastResponse = [
            'success' => true,
            'status' => 200,
            'data' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function sendError(array $payload, int $statusCode): void
    {
        $this->lastResponse = [
            'success' => false,
            'status' => $statusCode,
            'data' => $payload,
        ];
    }
}
