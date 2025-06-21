<?php
namespace MonitorTests\Managers;

/**
 * Class TestLimitManager
 *
 * Gestiona los límites de intentos de los tests por usuario y muestra un mensaje si se alcanza el límite.
 */
class TestLimitManager {
    /**
     * Definición de límites de intentos por tipo de test.
     * @var int[]
     */
    private $limits = [
        'ansiedad'      => 1,
        'bronca'        => 3,
        'depresion'     => 2,
        'sugestion'     => 5,
        'attending_emotions' => 1,
        // agregar más tipos según configuración
    ];

    public function __construct() {
        // Registrar hooks
        add_action('init', [\$this, 'registerHooks']);
    }

    /**
     * Registra las acciones y filtros necesarios para aplicar límites.
     */
    public function registerHooks() {
        // Antes de mostrar el formulario, comprobamos si el usuario ha alcanzado el límite
        add_filter('monitor_test_form_shortcode', [\$this, 'maybeLimitTestForm'], 5, 3);
    }

    /**
     * Filtra la salida del shortcode del formulario para aplicar límite.
     *
     * @param string \$output  HTML generado original del formulario.
     * @param array  \$atts    Atributos del shortcode.
     * @param string \$tag     Nombre del shortcode.
     * @return string          HTML modificado o mensaje de límite.
     */
    public function maybeLimitTestForm(\$output, \$atts, \$tag) {
        if (!is_user_logged_in()) {
            return \$output;
        }

        // Obtener el tipo de test desde la URL o atributos
        \$type = '';
        if (!empty(\$atts['type'])) {
            \$type = sanitize_text_field(\$atts['type']);
        } elseif (isset(\$_GET['type'])) {
            \$type = sanitize_text_field(\$_GET['type']);
        }

        if (!\$type) {
            return \$output;
        }

        // Comprobar si limit reached
        if (\$this->isLimitReached(\$type)) {
            return '<div class="mensaje error">Has alcanzado el límite de intentos para este test.</div>';
        }

        return \$output;
    }

    /**
     * Determina si el usuario actual ha alcanzado el límite de intentos para un tipo de test.
     *
     * @param string \$type
     * @return bool
     */
    private function isLimitReached(\$type) {
        \$user_id = get_current_user_id();
        \$limit = isset(\$this->limits[\$type]) ? intval(\$this->limits[\$type]) : 1;

        // Query de resultados publicados para este usuario y test
        \$query = new \WP_Query([
            'post_type'      => 'resultado_monitor',
            'author'         => \$user_id,
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => 'tipo_test',
                    'value' => sanitize_key(\$type),
                ],
            ],
        ]);

        return \$query->found_posts >= \$limit;
    }
}

// Instanciamos el manager para que registre los hooks
new TestLimitManager();
