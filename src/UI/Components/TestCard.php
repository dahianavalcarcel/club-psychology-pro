<?php
namespace ClubPsychologyPro\UI\Components;

use ClubPsychologyPro\Tests\TestManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TestCard
 *
 * Renders a summary card for an available psychological test.
 */
class TestCard extends AbstractComponent {

    /**
     * Constructor: sets component ID and hooks.
     */
    public function __construct() {
        parent::__construct( 'test-card' );
    }

    /**
     * Register CSS/JS assets for the test card.
     */
    protected function registerAssets(): void {
        wp_register_style(
            'cpp-test-card',
            CPP_PLUGIN_URL . 'assets/css/test-card.css',
            [],
            '2.0.0'
        );
        wp_register_script(
            'cpp-test-card',
            CPP_PLUGIN_URL . 'assets/js/test-card.js',
            ['jquery'],
            '2.0.0',
            true
        );
    }

    /**
     * Register hooks: shortcode and enqueue.
     */
    protected function registerHooks(): void {
        add_shortcode( 'cpp_test_card', [ $this, 'handleShortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    }

    /**
     * Enqueue assets for this component.
     */
    public function enqueueAssets(): void {
        if ( did_action( 'wp_enqueue_scripts' ) ) {
            wp_enqueue_style( 'cpp-test-card' );
            wp_enqueue_script( 'cpp-test-card' );
        }
    }

    /**
     * Shortcode handler: renders one or more test cards.
     *
     * @param array $atts Shortcode attributes: 'type' optional filter
     * @return string HTML output
     */
    public function handleShortcode( array $atts ): string {
        $atts = shortcode_atts([ 'type' => '' ], $atts, 'cpp_test_card' );
        return $this->render( [ 'type' => sanitize_text_field( $atts['type'] ) ] );
    }

    /**
     * Render the test card(s).
     *
     * @param array $context Context data, including optional 'type'
     * @return string HTML markup
     */
    public function render( array $context = [] ): string {
        $type = $context['type'] ?? '';
        $tests = TestManager::getInstance()->getAvailableTests( $type );

        if ( empty( $tests ) ) {
            return '<p class="cpp-no-tests">' . esc_html__( 'No hay tests disponibles.', 'club-psychology-pro' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="cpp-test-card-container">
            <?php foreach ( $tests as $test ) : ?>
                <div class="cpp-test-card">
                    <h3 class="cpp-test-title"><?php echo esc_html( $test->getLabel() ); ?></h3>
                    <?php if ( $test->getDescription() ) : ?>
                        <p class="cpp-test-description"><?php echo esc_html( $test->getDescription() ); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $test->getStartUrl() ); ?>" class="cpp-test-button">
                        <?php echo esc_html__( 'Iniciar Test', 'club-psychology-pro' ); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
