<?php
namespace ClubPsychologyPro\UI\Components;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AbstractComponent
 *
 * Base class for all UI components in the admin/front-end.
 * Provides lifecycle hooks for registering assets, hooks, and rendering.
 */
abstract class AbstractComponent {

    /**
     * Unique identifier for the component.
     *
     * @var string
     */
    protected string $id;

    /**
     * Component constructor.
     *
     * @param string $id   Unique component ID (used for asset handles, etc).
     */
    public function __construct( string $id ) {
        $this->id = $id;

        // Register component on WP init
        add_action( 'init', [ $this, 'register' ] );
    }

    /**
     * Final registration step. Calls individual registration methods.
     *
     * @return void
     */
    final public function register(): void {
        $this->registerAssets();
        $this->registerHooks();
    }

    /**
     * Register scripts & styles needed by this component.
     * Override to enqueue/register your assets.
     *
     * @return void
     */
    protected function registerAssets(): void {
        // no-op by default
    }

    /**
     * Register any WordPress hooks (actions/filters) for this component.
     * Override to hook into WP.
     *
     * @return void
     */
    protected function registerHooks(): void {
        // no-op by default
    }

    /**
     * Render the component output.
     *
     * @param array $context  Optional data/context for rendering.
     * @return string         HTML markup for the component.
     */
    abstract public function render( array $context = [] ): string;

    /**
     * Helper to safely escape attributes.
     *
     * @param array $attrs
     * @return string
     */
    protected function renderAttributes( array $attrs ): string {
        $output = '';
        foreach ( $attrs as $key => $value ) {
            $output .= sprintf(
                ' %s="%s"',
                esc_attr( $key ),
                esc_attr( $value )
            );
        }
        return $output;
    }
}
