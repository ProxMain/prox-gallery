<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Throwable;

/**
 * Base class for authenticated AJAX action controllers with nonce validation.
 */
abstract class AbstractActionController implements ControllerInterface
{
    /**
     * @var array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    private array $registeredActions = [];

    public function boot(): void
    {
        $configuredActions = $this->actions();

        if ($configuredActions === []) {
            return;
        }

        /**
         * Filters registered action callbacks for a concrete action controller.
         *
         * @param array<string, array{callback:string, nonce_action?:string, capability?:string}> $configuredActions
         */
        $configuredActions = (array) \apply_filters(
            'prox_gallery/action_controller/' . $this->id() . '/actions',
            $configuredActions,
            $this
        );

        foreach ($configuredActions as $action => $definition) {
            if (! is_string($action) || $action == '' || ! is_array($definition)) {
                continue;
            }

            $callback = isset($definition['callback']) ? (string) $definition['callback'] : '';

            if ($callback == '' || ! method_exists($this, $callback)) {
                continue;
            }

            $this->registeredActions[$action] = [
                'callback' => $callback,
                'nonce_action' => isset($definition['nonce_action']) ? (string) $definition['nonce_action'] : $this->defaultNonceAction($action),
                'capability' => isset($definition['capability']) ? (string) $definition['capability'] : $this->defaultCapability(),
            ];

            \add_action('wp_ajax_' . $action, [$this, 'handleAjaxRequest']);

            /**
             * Fires when an action handler is registered.
             *
             * @param string $action Action name.
             * @param self   $controller Action controller instance.
             */
            \do_action('prox_gallery/action_controller/' . $this->id() . '/registered', $action, $this);
        }

        /**
         * Fires after an action controller has completed registration.
         *
         * @param self $controller Action controller instance.
         */
        \do_action('prox_gallery/action_controller/' . $this->id() . '/booted', $this);
    }

    /**
     * Handles all registered AJAX requests by reading the incoming action.
     */
    public function handleAjaxRequest(): void
    {
        $action = isset($_REQUEST['action']) ? (string) \wp_unslash($_REQUEST['action']) : '';

        if ($action == '' || ! isset($this->registeredActions[$action])) {
            \wp_send_json_error(['message' => 'Unknown action.'], 404);
        }

        $definition = $this->registeredActions[$action];

        if (! $this->currentUserCan((string) $definition['capability'], $action)) {
            \wp_send_json_error(['message' => 'You are not allowed to perform this action.'], 403);
        }

        if (! $this->isValidNonce((string) $definition['nonce_action'])) {
            \wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        }

        /**
         * Fires before dispatching an AJAX action callback.
         *
         * @param string $action Action name.
         * @param self   $controller Action controller instance.
         */
        \do_action('prox_gallery/action_controller/' . $this->id() . '/before_dispatch', $action, $this);

        try {
            $callback = (string) $definition['callback'];

            /** @var array<string, mixed> $payload */
            $payload = is_array($_POST) ? \wp_unslash($_POST) : [];

            /** @var array<string, mixed> $response */
            $response = $this->{$callback}($payload, $action);

            /**
             * Filters successful response payload before it is returned.
             *
             * @param array<string, mixed> $response
             * @param string               $action
             * @param self                 $controller
             */
            $response = (array) \apply_filters(
                'prox_gallery/action_controller/' . $this->id() . '/response',
                $response,
                $action,
                $this
            );

            /**
             * Fires after an AJAX action callback succeeds.
             *
             * @param string               $action
             * @param array<string, mixed> $response
             * @param self                 $controller
             */
            \do_action(
                'prox_gallery/action_controller/' . $this->id() . '/after_dispatch',
                $action,
                $response,
                $this
            );

            \wp_send_json_success($response);
        } catch (Throwable $exception) {
            /**
             * Fires when an AJAX action callback throws.
             *
             * @param string    $action
             * @param Throwable $exception
             * @param self      $controller
             */
            \do_action(
                'prox_gallery/action_controller/' . $this->id() . '/dispatch_failed',
                $action,
                $exception,
                $this
            );

            \wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    abstract protected function actions(): array;

    protected function defaultCapability(): string
    {
        return 'manage_options';
    }

    protected function defaultNonceAction(string $action): string
    {
        return $action;
    }

    private function isValidNonce(string $nonceAction): bool
    {
        if ($nonceAction === '') {
            return true;
        }

        $nonce = isset($_REQUEST['_ajax_nonce']) ? (string) \wp_unslash($_REQUEST['_ajax_nonce']) : '';

        if ($nonce == '') {
            return false;
        }

        if ((bool) \wp_verify_nonce($nonce, $nonceAction)) {
            return true;
        }

        return (bool) \wp_verify_nonce($nonce, 'wp_rest');
    }

    private function currentUserCan(string $capability, string $action): bool
    {
        $allowed = \function_exists('current_user_can') && \current_user_can($capability);

        /**
         * Filters action-level capability checks.
         *
         * @param bool   $allowed
         * @param string $action
         * @param string $capability
         * @param self   $controller
         */
        return (bool) \apply_filters(
            'prox_gallery/action_controller/' . $this->id() . '/can_run',
            $allowed,
            $action,
            $capability,
            $this
        );
    }
}
