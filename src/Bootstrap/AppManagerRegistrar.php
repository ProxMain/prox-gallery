<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;
use Prox\ProxGallery\Contracts\ManagerInterface;
use Prox\ProxGallery\Controllers\Admin\AdminConfigContributorRegistry;
use Prox\ProxGallery\Flows\AdminFlow;
use Prox\ProxGallery\Flows\FrontendFlow;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\FlowManager;
use Prox\ProxGallery\Managers\ModuleManager;
use Prox\ProxGallery\Modules\Admin\Controllers\AdminGalleryController;
use Prox\ProxGallery\Modules\Admin\Controllers\TemplateSettingsActionController;
use Prox\ProxGallery\Modules\Admin\Controllers\TrackingActionController;
use Prox\ProxGallery\Modules\AdminModule;
use Prox\ProxGallery\Modules\CoreModule;
use Prox\ProxGallery\Modules\DevelopmentSeed\Controllers\DevelopmentSeedCliController;
use Prox\ProxGallery\Modules\DevelopmentSeed\DevelopmentSeedModule;
use Prox\ProxGallery\Modules\Frontend\Controllers\FrontendGalleryController;
use Prox\ProxGallery\Modules\FrontendModule;
use Prox\ProxGallery\Modules\Gallery\Controllers\GalleryActionController;
use Prox\ProxGallery\Modules\Gallery\GalleryModule;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaCategoryActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaLibraryCliController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaManagerActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaUploadController;
use Prox\ProxGallery\Modules\MediaLibrary\MediaLibraryModule;
use Prox\ProxGallery\Modules\OpenAi\Controllers\OpenAiActionController;
use Prox\ProxGallery\Modules\OpenAi\OpenAiModule;

/**
 * Populates lifecycle managers from the application container.
 */
final class AppManagerRegistrar
{
    public function __construct(private bool $developmentSeedEnabled)
    {
    }

    /**
     * @param callable(ManagerInterface): void $addManager
     */
    public function register(Container $container, callable $addManager): void
    {
        $this->registerModuleManager($container, $addManager);
        $this->registerFlowManager($container, $addManager);
        $this->registerControllerManager($container, $addManager);
        $this->registerCliManager($container, $addManager);
    }

    /**
     * @param callable(ManagerInterface): void $addManager
     */
    private function registerModuleManager(Container $container, callable $addManager): void
    {
        $manager = $container->get(ModuleManager::class);
        $manager->add($container->get(CoreModule::class));
        $manager->add($container->get(AdminModule::class));
        $manager->add($container->get(FrontendModule::class));
        $manager->add($container->get(OpenAiModule::class));
        $manager->add($container->get(GalleryModule::class));
        $manager->add($container->get(MediaLibraryModule::class));

        if ($this->developmentSeedEnabled) {
            $manager->add($container->get(DevelopmentSeedModule::class));
        }

        $addManager($manager);
    }

    /**
     * @param callable(ManagerInterface): void $addManager
     */
    private function registerFlowManager(Container $container, callable $addManager): void
    {
        $manager = $container->get(FlowManager::class);
        $manager->add($container->get(AdminFlow::class));
        $manager->add($container->get(FrontendFlow::class));
        $addManager($manager);
    }

    /**
     * @param callable(ManagerInterface): void $addManager
     */
    private function registerControllerManager(Container $container, callable $addManager): void
    {
        $manager = $container->get(ControllerManager::class);
        $registry = $container->get(AdminConfigContributorRegistry::class);
        $controllers = [
            $container->get(AdminGalleryController::class),
            $container->get(FrontendGalleryController::class),
            $container->get(MediaUploadController::class),
            $container->get(GalleryActionController::class),
            $container->get(MediaManagerActionController::class),
            $container->get(MediaCategoryActionController::class),
            $container->get(TemplateSettingsActionController::class),
            $container->get(TrackingActionController::class),
            $container->get(OpenAiActionController::class),
        ];

        foreach ($controllers as $controller) {
            $manager->add($controller);

            if ($controller instanceof AdminConfigContributorInterface) {
                $registry->addContributor($controller);
            }
        }

        $addManager($manager);
    }

    /**
     * @param callable(ManagerInterface): void $addManager
     */
    private function registerCliManager(Container $container, callable $addManager): void
    {
        $manager = $container->get(CliManager::class);
        $manager->add($container->get(MediaLibraryCliController::class));

        if ($this->developmentSeedEnabled) {
            $manager->add($container->get(DevelopmentSeedCliController::class));
        }

        $addManager($manager);
    }
}
