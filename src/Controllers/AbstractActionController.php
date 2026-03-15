<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use InvalidArgumentException;
use LogicException;
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
                throw new LogicException('Invalid action definition key for controller "' . $this->id() . '".');
            }

            $callback = isset($definition['callback']) ? (string) $definition['callback'] : '';
            $nonceAction = isset($definition['nonce_action']) ? (string) $definition['nonce_action'] : '';
            $capability = isset($definition['capability']) ? (string) $definition['capability'] : '';

            if ($callback == '' || ! method_exists($this, $callback)) {
                throw new LogicException(
                    'Invalid callback definition for action "' . $action . '" in controller "' . $this->id() . '".'
                );
            }

            if ($nonceAction == '') {
                throw new LogicException(
                    'Missing nonce_action for action "' . $action . '" in controller "' . $this->id() . '".'
                );
            }

            if ($capability == '') {
                throw new LogicException(
                    'Missing capability for action "' . $action . '" in controller "' . $this->id() . '".'
                );
            }

            $this->registeredActions[$action] = [
                'callback' => $callback,
                'nonce_action' => $nonceAction,
                'capability' => $capability,
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
        $action = $this->requestString('action');

        if ($action == '' || ! isset($this->registeredActions[$action])) {
            $this->sendError(['message' => 'Unknown action.'], 404);
            return;
        }

        $definition = $this->registeredActions[$action];

        if (! $this->currentUserCan((string) $definition['capability'], $action)) {
            $this->sendError(['message' => 'You are not allowed to perform this action.'], 403);
            return;
        }

        if (! $this->isValidNonce((string) $definition['nonce_action'])) {
            $this->sendError(['message' => 'Nonce verification failed.'], 403);
            return;
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

            $payload = $this->requestPostPayload();

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

            $this->sendSuccess($response);
            return;
        } catch (InvalidArgumentException $exception) {
            /**
             * Fires when an AJAX action callback throws a validation/input exception.
             *
             * @param string                   $action
             * @param InvalidArgumentException $exception
             * @param self                     $controller
             */
            \do_action(
                'prox_gallery/action_controller/' . $this->id() . '/dispatch_invalid',
                $action,
                $exception,
                $this
            );

            $this->sendError(['message' => $exception->getMessage()], 400);
            return;
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

            \error_log(
                sprintf(
                    '[prox-gallery] AJAX action failed controller=%s action=%s error=%s',
                    $this->id(),
                    $action,
                    $exception->getMessage()
                )
            );

            $this->sendError(['message' => 'Request failed.'], 500);
            return;
        }
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    abstract protected function actions(): array;

    private function isValidNonce(string $nonceAction): bool
    {
        if ($nonceAction === '') {
            return true;
        }

        $nonce = $this->requestString('_ajax_nonce');

        if ($nonce == '') {
            return false;
        }

        return (bool) \wp_verify_nonce($nonce, $nonceAction);
    }

    private function requestString(string $key): string
    {
        $postValue = \filter_input(\INPUT_POST, $key, \FILTER_UNSAFE_RAW, \FILTER_NULL_ON_FAILURE);

        if (is_string($postValue)) {
            return (string) \wp_unslash($postValue);
        }

        $getValue = \filter_input(\INPUT_GET, $key, \FILTER_UNSAFE_RAW, \FILTER_NULL_ON_FAILURE);

        if (is_string($getValue)) {
            return (string) \wp_unslash($getValue);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPostPayload(): array
    {
        $payload = (array) \filter_input_array(\INPUT_POST, \FILTER_DEFAULT);

        /** @var array<string, mixed> $normalizedPayload */
        $normalizedPayload = \wp_unslash($payload);

        return $normalizedPayload;
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

    /**
     * @param array<string, mixed> $payload
     */
    protected function sendSuccess(array $payload): void
    {
        \wp_send_json_success($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function sendError(array $payload, int $statusCode): void
    {
        \wp_send_json_error($payload, $statusCode);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, string> $actions
     * @param array<string, mixed> $extras
     *
     * @return array<string, mixed>
     */
    protected function extendAdminActionConfig(
        array $config,
        string $controllerKey,
        array $actions,
        array $extras = []
    ): array {
        $controllers = [];

        if (isset($config['action_controllers']) && is_array($config['action_controllers'])) {
            $controllers = $config['action_controllers'];
        }

        $controllerConfig = [];

        foreach ($actions as $key => $action) {
            if (! is_string($key) || $key === '' || ! is_string($action) || $action === '') {
                continue;
            }

            $controllerConfig[$key] = [
                'action' => $action,
                'nonce' => \wp_create_nonce($action),
            ];
        }

        $controllers[$controllerKey] = [
            ...$controllerConfig,
            ...$extras,
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
