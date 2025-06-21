<?php

namespace ClubPsychologyPro\UI;

/**
 * ComponentRegistry
 *
 * Lleva un registro de los componentes de UI del plugin y ejecuta su registro.
 */
class ComponentRegistry {
    /**
     * @var ComponentInterface[]
     */
    protected array $components = [];

    /**
     * Agrega un componente al registro.
     *
     * @param ComponentInterface $component
     */
    public function addComponent(ComponentInterface $component): void
    {
        $this->components[] = $component;
    }

    /**
     * Ejecuta el método register() de todos los componentes agregados.
     */
    public function registerAll(): void
    {
        foreach ($this->components as $component) {
            $component->register();
        }
    }

    /**
     * Inicializa el registry y registra los hooks necesarios.
     *
     * @return ComponentRegistry
     */
    public static function init(): ComponentRegistry
    {
        $registry = new self();

        // Ejemplo: añadir componentes por defecto
        // $registry->addComponent(new AdminDashboard());
        // $registry->addComponent(new SettingsPage());
        // $registry->addComponent(new TestManagement());

        // Hook para lanzar el registro en el momento adecuado
        add_action('init', [ $registry, 'registerAll' ], 0);

        return $registry;
    }
}

// Arranque automático del ComponentRegistry
do_action('club_psychology_pro_loaded', []);
ComponentRegistry::init();
