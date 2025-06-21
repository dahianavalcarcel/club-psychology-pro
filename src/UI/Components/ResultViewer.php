<?php
namespace ClubPsychologyPro\UI\Components;

use ClubPsychologyPro\Tests\TestManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ResultViewer
 *
 * Renders a psychological test result on the front-end via shortcode [cpp_result].
 */
class ResultViewer extends AbstractComponent {

    /**
     * ResultViewer constructor.
     */
    public function __construct() {
        parent::__construct( 'result-viewer' );
    }

    /**
     * Register CSS and JS assets for the result viewer.
     */
    protected function registerAssets(): void {
        // Adjust CPP_PLUGIN_URL constant to your plugin's URL root
        wp_register_style(
            'cpp-result-viewer',
            CPP_PLUGIN_URL . 'assets/css/result-viewer.css',
            [],
            '2.0.0'
        );
        wp_register_script(
            'cpp-result-viewer',
            CPP_PLUGIN_URL . 'assets/js/result-viewer.js',
            [ 'chart.js' ],
            '2.0.0',
            true
        );
    }

    /**
     * Hook into WordPress: register shortcode and enqueue assets.
     */
    protected function registerHooks(): void {
        add_shortcode( 'cpp_result', [ $this, 'handleShortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    }

    /**
     * Enqueue the viewer's assets when needed.
     */
    public function enqueueAssets(): void {
        if ( did_action( 'wp_enqueue_scripts' ) ) {
            wp_enqueue_style( 'cpp-result-viewer' );
            wp_enqueue_script( 'cpp-result-viewer' );
        }
    }

    /**
     * Shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string     Rendered HTML.
     */
    public function handleShortcode( array $atts ): string {
        $atts = shortcode_atts( [
            'id' => 0,
        ], $atts, 'cpp_result' );

        return $this->render( [ 'id' => (int) $atts['id'] ] );
    }

    /**
     * Render the result viewer.
     *
     * @param array $context Context including 'id' of the result.
     * @return string        HTML markup.
     */
    public function render( array $context = [] ): string {
        $id = $context['id'] ?? 0;
        if ( ! $id ) {
            return '<div class="cpp-result-error">'
                 . esc_html__( 'Resultado no válido.', 'club-psychology-pro' )
                 . '</div>';
        }

        $result = TestManager::getInstance()->getResultById( $id );
        if ( ! $result ) {
            return '<div class="cpp-result-error">'
                 . esc_html__( 'Resultado no encontrado.', 'club-psychology-pro' )
                 . '</div>';
        }

        ob_start();
        ?>
        <div class="cpp-result-viewer">
            <h2 class="cpp-result-title"><?php echo esc_html( $result->getTestTitle() ); ?></h2>
            <div class="cpp-result-summary">
                <span class="cpp-result-score">
                    <?php echo esc_html( $result->getScore() ); ?>/<?php echo esc_html( $result->getMaxScore() ); ?>
                </span>
                <span class="cpp-result-level">
                    <?php echo esc_html( $result->getLevel() ); ?>
                </span>
            </div>
            <p class="cpp-result-description">
                <?php echo esc_html( $result->getDescription() ); ?>
            </p>
            <canvas
                id="cpp-result-chart-<?php echo esc_attr( $id ); ?>"
                data-score="<?php echo esc_attr( $result->getScore() ); ?>"
                data-max="<?php echo esc_attr( $result->getMaxScore() ); ?>"
                class="cpp-result-chart"
            ></canvas>
        </div>
        <?php
        return ob_get_clean();
    }
}
