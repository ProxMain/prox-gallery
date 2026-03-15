<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Controllers\Admin\AdminAssetLoader;
use Prox\ProxGallery\Controllers\Admin\AdminConfigContributorRegistry;
use Prox\ProxGallery\Controllers\Admin\AdminConfigProvider;
use Prox\ProxGallery\Controllers\Admin\AdminMenuRegistrar;
use Prox\ProxGallery\Flows\AdminFlow;
use Prox\ProxGallery\Flows\FrontendFlow;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\FlowManager;
use Prox\ProxGallery\Managers\ModuleManager;
use Prox\ProxGallery\Modules\Admin\Controllers\AdminGalleryController;
use Prox\ProxGallery\Modules\Admin\Controllers\TemplateSettingsActionController;
use Prox\ProxGallery\Modules\Admin\Controllers\TrackingActionController;
use Prox\ProxGallery\Modules\Admin\Services\AdminConfigurationService;
use Prox\ProxGallery\Modules\Admin\Services\TemplateCustomizationService;
use Prox\ProxGallery\Modules\Admin\Services\TrackingSummaryService;
use Prox\ProxGallery\Modules\AdminModule;
use Prox\ProxGallery\Modules\CoreModule;
use Prox\ProxGallery\Modules\DevelopmentSeed\Controllers\DevelopmentSeedCliController;
use Prox\ProxGallery\Modules\DevelopmentSeed\DevelopmentSeedModule;
use Prox\ProxGallery\Modules\DevelopmentSeed\Services\DevelopmentSeedService;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryRepositoryInterface;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryTemplateRegistryInterface;
use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryTemplateRendererInterface;
use Prox\ProxGallery\Modules\Frontend\Controllers\FrontendGalleryBlockController;
use Prox\ProxGallery\Modules\Frontend\Controllers\FrontendGalleryController;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryRepository;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryTemplateRegistry;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryTemplateRenderer;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\FrontendModule;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryPageProvisionerInterface;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use Prox\ProxGallery\Modules\Gallery\Controllers\GalleryActionController;
use Prox\ProxGallery\Modules\Gallery\GalleryModule;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryPageProvisioningService;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaCategoryActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaLibraryCliController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaManagerActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaUploadController;
use Prox\ProxGallery\Modules\MediaLibrary\MediaLibraryModule;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerMetadataService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerSyncService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerTrackSelectionService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;
use Prox\ProxGallery\Modules\OpenAi\Controllers\OpenAiActionController;
use Prox\ProxGallery\Modules\OpenAi\OpenAiModule;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\States\AdminConfigurationState;
use Prox\ProxGallery\States\FrontendGalleryState;

/**
 * Registers container bindings for the application lifecycle.
 */
final class AppBindingRegistrar
{
    public function __construct(private bool $developmentSeedEnabled)
    {
    }

    public function register(Container $container): void
    {
        $this->registerManagerBindings($container);
        $this->registerModuleBindings($container);
        $this->registerControllerBindings($container);
        $this->registerStateBindings($container);
        $this->registerPolicyBindings($container);
        $this->registerModelBindings($container);
        $this->registerServiceBindings($container);
        $this->registerFlowBindings($container);
        $this->registerCliBindings($container);
    }

    private function registerManagerBindings(Container $container): void
    {
        $container->set(ModuleManager::class, static fn (): ModuleManager => new ModuleManager());
        $container->set(FlowManager::class, static fn (): FlowManager => new FlowManager());
        $container->set(ControllerManager::class, static fn (): ControllerManager => new ControllerManager());
        $container->set(CliManager::class, static fn (): CliManager => new CliManager());
    }

    private function registerModuleBindings(Container $container): void
    {
        $container->set(CoreModule::class, static fn () => new CoreModule());
        $container->set(AdminModule::class, static fn () => new AdminModule());
        $container->set(FrontendModule::class, static fn () => new FrontendModule());
        $container->set(OpenAiModule::class, static fn () => new OpenAiModule());
        $container->set(
            GalleryModule::class,
            static fn (Container $container) => new GalleryModule(
                $container->get(GalleryService::class)
            )
        );
        $container->set(
            MediaLibraryModule::class,
            static fn (Container $container) => new MediaLibraryModule(
                $container->get(TrackUploadedImageService::class),
                $container->get(MediaCategoryService::class)
            )
        );

        if ($this->developmentSeedEnabled) {
            $container->set(
                DevelopmentSeedModule::class,
                static fn (Container $container) => new DevelopmentSeedModule(
                    $container->get(DevelopmentSeedService::class)
                )
            );
        }
    }

    private function registerControllerBindings(Container $container): void
    {
        $container->set(
            AdminGalleryController::class,
            static fn (Container $container) => new AdminGalleryController(
                $container->get(AdminMenuRegistrar::class),
                $container->get(AdminAssetLoader::class),
                $container->get(AdminConfigProvider::class)
            )
        );
        $container->set(
            FrontendGalleryController::class,
            static fn (Container $container) => new FrontendGalleryController(
                $container->get(FrontendGalleryService::class),
                $container->get(FrontendTrackingService::class)
            )
        );
        $container->set(
            FrontendGalleryBlockController::class,
            static fn (Container $container) => new FrontendGalleryBlockController(
                $container->get(FrontendGalleryController::class),
                $container->get(GalleryService::class)
            )
        );
        $container->set(
            MediaUploadController::class,
            static fn (Container $container) => new MediaUploadController(
                $container->get(TrackUploadedImageService::class)
            )
        );
        $container->set(
            MediaManagerActionController::class,
            static fn (Container $container) => new MediaManagerActionController(
                $container->get(UploadedImageQueueModel::class),
                $container->get(TrackUploadedImageService::class),
                $container->get(MediaManagerListService::class),
                $container->get(MediaManagerSyncService::class),
                $container->get(MediaManagerTrackSelectionService::class),
                $container->get(MediaManagerMetadataService::class)
            )
        );
        $container->set(
            GalleryActionController::class,
            static fn (Container $container) => new GalleryActionController(
                $container->get(GalleryService::class),
                $container->get(FrontendGalleryService::class)
            )
        );
        $container->set(
            MediaCategoryActionController::class,
            static fn (Container $container) => new MediaCategoryActionController(
                $container->get(MediaCategoryService::class),
                $container->get(UploadedImageQueueModel::class)
            )
        );
        $container->set(
            TemplateSettingsActionController::class,
            static fn (Container $container) => new TemplateSettingsActionController(
                $container->get(TemplateCustomizationService::class)
            )
        );
        $container->set(
            TrackingActionController::class,
            static fn (Container $container) => new TrackingActionController(
                $container->get(TrackingSummaryService::class)
            )
        );
        $container->set(
            OpenAiActionController::class,
            static fn (Container $container) => new OpenAiActionController(
                $container->get(OpenAiSettingsService::class),
                $container->get(OpenAiStoryService::class)
            )
        );
    }

    private function registerStateBindings(Container $container): void
    {
        $container->set(AdminConfigurationState::class, static fn () => new AdminConfigurationState());
        $container->set(FrontendGalleryState::class, static fn () => new FrontendGalleryState());
    }

    private function registerPolicyBindings(Container $container): void
    {
        $container->set(AdminCapabilityPolicy::class, static fn () => new AdminCapabilityPolicy());
        $container->set(FrontendVisibilityPolicy::class, static fn () => new FrontendVisibilityPolicy());
    }

    private function registerModelBindings(Container $container): void
    {
        $container->set(GalleryCollectionModel::class, static fn () => new GalleryCollectionModel());
        $container->set(
            GalleryRepositoryInterface::class,
            static fn (Container $container): GalleryRepositoryInterface => $container->get(GalleryCollectionModel::class)
        );
        $container->set(UploadedImageQueueModel::class, static fn () => new UploadedImageQueueModel());
    }

    private function registerServiceBindings(Container $container): void
    {
        $container->set(
            AdminConfigurationService::class,
            static fn (Container $container) => new AdminConfigurationService(
                $container->get(AdminConfigurationState::class),
                $container->get(AdminCapabilityPolicy::class)
            )
        );
        $container->set(
            FrontendGalleryService::class,
            static fn (Container $container) => new FrontendGalleryService(
                $container->get(FrontendGalleryState::class),
                $container->get(FrontendVisibilityPolicy::class),
                $container->get(FrontendGalleryRepositoryInterface::class),
                $container->get(FrontendGalleryTemplateRendererInterface::class),
                $container->get(FrontendGalleryTemplateRegistryInterface::class)
            )
        );
        $container->set(
            FrontendGalleryRepository::class,
            static fn (Container $container) => new FrontendGalleryRepository(
                $container->get(GalleryRepositoryInterface::class)
            )
        );
        $container->set(
            FrontendGalleryRepositoryInterface::class,
            static fn (Container $container): FrontendGalleryRepositoryInterface => $container->get(FrontendGalleryRepository::class)
        );
        $container->set(
            FrontendGalleryTemplateRenderer::class,
            static fn (Container $container) => new FrontendGalleryTemplateRenderer(
                $container->get(TemplateCustomizationService::class)
            )
        );
        $container->set(
            FrontendGalleryTemplateRendererInterface::class,
            static fn (Container $container): FrontendGalleryTemplateRendererInterface => $container->get(FrontendGalleryTemplateRenderer::class)
        );
        $container->set(
            FrontendGalleryTemplateRegistry::class,
            static fn (Container $container) => new FrontendGalleryTemplateRegistry(
                $container->get(FrontendGalleryTemplateRenderer::class)
            )
        );
        $container->set(
            FrontendGalleryTemplateRegistryInterface::class,
            static fn (Container $container): FrontendGalleryTemplateRegistryInterface => $container->get(FrontendGalleryTemplateRegistry::class)
        );
        $container->set(FrontendTrackingService::class, static fn () => new FrontendTrackingService());
        $container->set(
            TrackingSummaryService::class,
            static fn (Container $container) => new TrackingSummaryService(
                $container->get(FrontendTrackingService::class),
                $container->get(GalleryService::class),
                $container->get(UploadedImageQueueModel::class)
            )
        );
        $container->set(
            TrackUploadedImageService::class,
            static fn (Container $container) => new TrackUploadedImageService(
                $container->get(UploadedImageQueueModel::class)
            )
        );
        $container->set(
            GalleryService::class,
            static fn (Container $container) => new GalleryService(
                $container->get(GalleryRepositoryInterface::class),
                $container->get(GalleryPageProvisionerInterface::class)
            )
        );
        $container->set(GalleryPageProvisioningService::class, static fn () => new GalleryPageProvisioningService());
        $container->set(
            GalleryPageProvisionerInterface::class,
            static fn (Container $container): GalleryPageProvisionerInterface => $container->get(GalleryPageProvisioningService::class)
        );
        $container->set(MediaCategoryService::class, static fn () => new MediaCategoryService());
        $container->set(
            MediaManagerListService::class,
            static fn (Container $container) => new MediaManagerListService(
                $container->get(UploadedImageQueueModel::class)
            )
        );
        $container->set(
            MediaManagerSyncService::class,
            static fn (Container $container) => new MediaManagerSyncService(
                $container->get(UploadedImageQueueModel::class),
                $container->get(TrackUploadedImageService::class)
            )
        );
        $container->set(
            MediaManagerMetadataService::class,
            static fn (Container $container) => new MediaManagerMetadataService(
                $container->get(TrackUploadedImageService::class)
            )
        );
        $container->set(
            MediaManagerTrackSelectionService::class,
            static fn (Container $container) => new MediaManagerTrackSelectionService(
                $container->get(TrackUploadedImageService::class)
            )
        );
        $container->set(AdminMenuRegistrar::class, static fn () => new AdminMenuRegistrar());
        $container->set(AdminAssetLoader::class, static fn () => new AdminAssetLoader());
        $container->set(AdminConfigProvider::class, static fn () => new AdminConfigProvider());
        $container->set(AdminConfigContributorRegistry::class, static fn () => new AdminConfigContributorRegistry());
        $container->set(
            TemplateCustomizationService::class,
            static fn (Container $container) => new TemplateCustomizationService(
                $container->get(AdminConfigurationState::class)
            )
        );
        $container->set(
            OpenAiSettingsService::class,
            static fn (Container $container) => new OpenAiSettingsService(
                $container->get(AdminConfigurationState::class)
            )
        );
        $container->set(
            OpenAiStoryService::class,
            static fn (Container $container) => new OpenAiStoryService(
                $container->get(OpenAiSettingsService::class)
            )
        );

        if ($this->developmentSeedEnabled) {
            $container->set(
                DevelopmentSeedService::class,
                static fn (Container $container) => new DevelopmentSeedService(
                    $container->get(GalleryService::class),
                    $container->get(FrontendGalleryService::class),
                    $container->get(FrontendTrackingService::class),
                    $container->get(MediaCategoryService::class),
                    $container->get(TrackUploadedImageService::class),
                    $container->get(UploadedImageQueueModel::class)
                )
            );
        }
    }

    private function registerFlowBindings(Container $container): void
    {
        $container->set(
            AdminFlow::class,
            static fn (Container $container) => new AdminFlow(
                $container->get(AdminCapabilityPolicy::class),
                $container->get(AdminConfigurationState::class),
                $container->get(AdminConfigurationService::class)
            )
        );
        $container->set(
            FrontendFlow::class,
            static fn (Container $container) => new FrontendFlow(
                $container->get(FrontendVisibilityPolicy::class),
                $container->get(FrontendGalleryState::class),
                $container->get(FrontendGalleryService::class)
            )
        );
    }

    private function registerCliBindings(Container $container): void
    {
        $container->set(
            MediaLibraryCliController::class,
            static fn (Container $container) => new MediaLibraryCliController(
                $container->get(UploadedImageQueueModel::class),
                $container->get(TrackUploadedImageService::class)
            )
        );

        if ($this->developmentSeedEnabled) {
            $container->set(
                DevelopmentSeedCliController::class,
                static fn (Container $container) => new DevelopmentSeedCliController(
                    $container->get(DevelopmentSeedService::class)
                )
            );
        }
    }
}
