<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Contracts\ManagerInterface;
use Prox\ProxGallery\Controllers\AdminGalleryController;
use Prox\ProxGallery\Controllers\FrontendGalleryController;
use Prox\ProxGallery\Flows\AdminFlow;
use Prox\ProxGallery\Flows\FrontendFlow;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\FlowManager;
use Prox\ProxGallery\Managers\ModuleManager;
use Prox\ProxGallery\Models\GalleryModel;
use Prox\ProxGallery\Modules\AdminModule;
use Prox\ProxGallery\Modules\CoreModule;
use Prox\ProxGallery\Modules\FrontendModule;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaLibraryCliController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaManagerActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaUploadController;
use Prox\ProxGallery\Modules\MediaLibrary\MediaLibraryModule;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\Services\AdminConfigurationService;
use Prox\ProxGallery\Services\FrontendGalleryService;
use Prox\ProxGallery\States\AdminConfigurationState;
use Prox\ProxGallery\States\FrontendGalleryState;
use Psr\Container\ContainerInterface;

/**
 * Application composition root.
 */
final class App
{
    private Container $container;

    /**
     * @var array<string, ManagerInterface>
     */
    private array $managers = [];

    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Creates a new application instance.
     */
    public static function make(): self
    {
        return new self(new Container());
    }

    /**
     * Boots the application.
     */
    public function boot(): void
    {
        $this->registerBindings();
        $this->registerManagers();
        $this->bootManagers();
    }

    /**
     * Registers container bindings.
     */
    private function registerBindings(): void
    {
        $this->registerManagerBindings();
        $this->registerModuleBindings();
        $this->registerControllerBindings();
        $this->registerStateBindings();
        $this->registerPolicyBindings();
        $this->registerModelBindings();
        $this->registerServiceBindings();
        $this->registerFlowBindings();
        $this->registerCliBindings();
    }

    /**
     * Registers manager bindings.
     */
    private function registerManagerBindings(): void
    {
        $this->container->set(ModuleManager::class, static fn (): ModuleManager => new ModuleManager());
        $this->container->set(FlowManager::class, static fn (): FlowManager => new FlowManager());
        $this->container->set(ControllerManager::class, static fn (): ControllerManager => new ControllerManager());
        $this->container->set(CliManager::class, static fn (): CliManager => new CliManager());
    }

    /**
     * Registers module bindings.
     */
    private function registerModuleBindings(): void
    {
        $this->container->set(CoreModule::class, static fn () => new CoreModule());
        $this->container->set(AdminModule::class, static fn () => new AdminModule());
        $this->container->set(FrontendModule::class, static fn () => new FrontendModule());
        $this->container->set(
            MediaLibraryModule::class,
            static fn (Container $container) => new MediaLibraryModule(
                $container->get(TrackUploadedImageService::class)
            )
        );
    }

    /**
     * Registers controller bindings.
     */
    private function registerControllerBindings(): void
    {
        $this->container->set(AdminGalleryController::class, static fn () => new AdminGalleryController());
        $this->container->set(FrontendGalleryController::class, static fn () => new FrontendGalleryController());
        $this->container->set(
            MediaUploadController::class,
            static fn (Container $container) => new MediaUploadController(
                $container->get(TrackUploadedImageService::class)
            )
        );
        $this->container->set(
            MediaManagerActionController::class,
            static fn (Container $container) => new MediaManagerActionController(
                $container->get(UploadedImageQueueModel::class),
                $container->get(TrackUploadedImageService::class)
            )
        );
    }

    /**
     * Registers state bindings.
     */
    private function registerStateBindings(): void
    {
        $this->container->set(AdminConfigurationState::class, static fn () => new AdminConfigurationState());
        $this->container->set(FrontendGalleryState::class, static fn () => new FrontendGalleryState());
    }

    /**
     * Registers policy bindings.
     */
    private function registerPolicyBindings(): void
    {
        $this->container->set(AdminCapabilityPolicy::class, static fn () => new AdminCapabilityPolicy());
        $this->container->set(FrontendVisibilityPolicy::class, static fn () => new FrontendVisibilityPolicy());
    }

    /**
     * Registers model bindings.
     */
    private function registerModelBindings(): void
    {
        $this->container->set(GalleryModel::class, static fn () => new GalleryModel());
        $this->container->set(UploadedImageQueueModel::class, static fn () => new UploadedImageQueueModel());
    }

    /**
     * Registers service bindings.
     */
    private function registerServiceBindings(): void
    {
        $this->container->set(
            AdminConfigurationService::class,
            static fn (Container $container) => new AdminConfigurationService(
                $container->get(AdminConfigurationState::class),
                $container->get(AdminCapabilityPolicy::class)
            )
        );
        $this->container->set(
            FrontendGalleryService::class,
            static fn (Container $container) => new FrontendGalleryService(
                $container->get(FrontendGalleryState::class),
                $container->get(FrontendVisibilityPolicy::class),
                $container->get(GalleryModel::class)
            )
        );
        $this->container->set(
            TrackUploadedImageService::class,
            static fn (Container $container) => new TrackUploadedImageService(
                $container->get(UploadedImageQueueModel::class)
            )
        );
    }

    /**
     * Registers flow bindings.
     */
    private function registerFlowBindings(): void
    {
        $this->container->set(
            AdminFlow::class,
            static fn (Container $container) => new AdminFlow(
                $container->get(AdminCapabilityPolicy::class),
                $container->get(AdminConfigurationState::class),
                $container->get(AdminConfigurationService::class)
            )
        );
        $this->container->set(
            FrontendFlow::class,
            static fn (Container $container) => new FrontendFlow(
                $container->get(FrontendVisibilityPolicy::class),
                $container->get(FrontendGalleryState::class),
                $container->get(FrontendGalleryService::class)
            )
        );
    }

    /**
     * Registers CLI bindings.
     */
    private function registerCliBindings(): void
    {
        $this->container->set(
            MediaLibraryCliController::class,
            static fn (Container $container) => new MediaLibraryCliController(
                $container->get(UploadedImageQueueModel::class),
                $container->get(TrackUploadedImageService::class)
            )
        );
    }

    /**
     * Registers manager instances.
     */
    private function registerManagers(): void
    {
        $this->registerModuleManager();
        $this->registerFlowManager();
        $this->registerControllerManager();
        $this->registerCliManager();
    }

    /**
     * Registers the module manager.
     */
    private function registerModuleManager(): void
    {
        $manager = $this->container->get(ModuleManager::class);
        $manager->add($this->container->get(CoreModule::class));
        $manager->add($this->container->get(AdminModule::class));
        $manager->add($this->container->get(FrontendModule::class));
        $manager->add($this->container->get(MediaLibraryModule::class));
        $this->addManager($manager);
    }

    /**
     * Registers the flow manager.
     */
    private function registerFlowManager(): void
    {
        $manager = $this->container->get(FlowManager::class);
        $manager->add($this->container->get(AdminFlow::class));
        $manager->add($this->container->get(FrontendFlow::class));
        $this->addManager($manager);
    }

    /**
     * Registers the controller manager.
     */
    private function registerControllerManager(): void
    {
        $manager = $this->container->get(ControllerManager::class);
        $manager->add($this->container->get(AdminGalleryController::class));
        $manager->add($this->container->get(FrontendGalleryController::class));
        $manager->add($this->container->get(MediaUploadController::class));
        $manager->add($this->container->get(MediaManagerActionController::class));
        $this->addManager($manager);
    }

    /**
     * Registers the CLI manager.
     */
    private function registerCliManager(): void
    {
        $manager = $this->container->get(CliManager::class);
        $manager->add($this->container->get(MediaLibraryCliController::class));
        $this->addManager($manager);
    }

    /**
     * Boots all registered managers.
     */
    private function bootManagers(): void
    {
        foreach ($this->managers as $manager) {
            $manager->boot();
        }
    }

    /**
     * Adds a manager to the lifecycle.
     */
    private function addManager(ManagerInterface $manager): void
    {
        $this->managers[$manager->id()] = $manager;
    }

    /**
     * Returns the container.
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
