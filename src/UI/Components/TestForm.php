<?php
namespace ClubPsychologyPro\UI\Components;

use ClubPsychologyPro\UI\Components\AbstractComponent;

/**
 * Component to render a psychological test form via shortcode.
 */
class TestForm extends AbstractComponent
{
    /**
     * Identifier for the shortcode.
     * @var string
     */
    protected string $slug = 'cpp_test_form';

    /**
     * Register the component: shortcode and asset hooks.
     */
    public function register(): void
    {
        add_shortcode($this->slug, [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue form-specific CSS/JS assets.
     */
    public function enqueueAssets(): void
    {
        // Styles for the test form
        wp_enqueue_style(
            'cpp-test-form',
            $this->assetUrl('css/test-form.css'),
            [],
            CPP_VERSION
        );

        // JavaScript to handle conditional logic and UX
        wp_enqueue_script(
            'cpp-test-form',
            $this->assetUrl('js/test-form.js'),
            ['jquery'],
            CPP_VERSION,
            true
        );
    }

    /**
     * Render the test form markup.
     * @param array $atts Shortcode attributes (test_id, type)
     * @param string|null $content
     * @return string HTML output
     */
    public function render(array $atts, ?string $content = null): string
    {
        // Normalize attributes
        $atts = shortcode_atts([
            'test_id' => 0,
            'type'    => '',
        ], $atts, $this->slug);

        $testId = intval($atts['test_id']);
        $testType = sanitize_text_field($atts['type']);

        // Optionally load configuration
        $config = \ClubPsychologyPro\Tests\TestManager::getInstance()
            ->getConfig($testType);

        // Pass data to template
        ob_start();
        include CPP_PLUGIN_DIR . 'templates/forms/test-form.php';
        return ob_get_clean();
    }
}
