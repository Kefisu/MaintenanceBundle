<?php

namespace Kefisu\Bundle\MaintenanceBundle\DependencyInjection;

use Kefisu\Bundle\MaintenanceBundle\EventListener\MaintenanceListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MaintenanceExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('maintenance.xml');

        $this->configureMaintenanceFilePath($config, $container);
    }

    /** @param mixed[] $config */
    private function configureMaintenanceFilePath(array $config, ContainerBuilder $container): void
    {
        $maintenanceFilePath = null;

        if (isset($config['maintenance_file_path']) && is_string($config['maintenance_file_path'])) {
            $maintenanceFilePath = $container->getParameterBag()->resolveValue($config['maintenance_file_path']);
        }

        if ($maintenanceFilePath === null) {
            $maintenanceFilePath = sys_get_temp_dir();
        }

        $container->setParameter('maintenance.file_path', $maintenanceFilePath);
    }
}
