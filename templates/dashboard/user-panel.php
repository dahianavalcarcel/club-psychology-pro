<?php
/**
 * src/templates/dashboard/user-panel.php
 *
 * Panel de usuario con listado de tests y formulario inline para solicitar uno nuevo.
 *
 * Variables disponibles:
 *   $tests (array) — Array de objetos WP_Post de los tests del usuario.
 */

if ( ! is_user_logged_in() ) {
    echo '<p>' . esc_html__( 'Debes iniciar sesión para ver tu panel.', 'club-psychology-pro' ) . '</p>';
    return;
}

// Si no se ha pasado $tests, lo obtenemos aquí por defecto
if ( ! isset( $tests ) ) {
    $tests = get_posts( [
        'author'      => get_current_user_id(),
        'post_type'   => 'cpp_test',
        'numberposts' => 10,
    ] );
}
?>

<div class="cpp-user-panel">
    <h2><?php esc_html_e( 'Mi Panel de Tests', 'club-psychology-pro' ); ?></h2>

    <button type="button"
            class="button button-primary js-open-test-form">
        <?php esc_html_e( 'Crear Nuevo Test', 'club-psychology-pro' ); ?>
    </button>

    <div id="cpp-test-form-container" style="display: none; margin-top: 1em;">
        <?php
        // Aquí renderizamos el formulario de solicitud de test
        echo do_shortcode( '[test_request_form]' );
        ?>
    </div>

    <hr>

    <h3><?php esc_html_e( 'Tus Tests Recientes', 'club-psychology-pro' ); ?></h3>

    <?php if ( ! empty( $tests ) ) : ?>
        <ul class="cpp-test-list">
            <?php foreach ( $tests as $test ) : ?>
                <li class="cpp-test-item">
                    <strong><?php echo esc_html( get_the_title( $test ) ); ?></strong>
                    — <?php echo esc_html( ucfirst( $test->post_status ) ); ?>
                    <button class="button cpp-btn-view-result"
                            data-id="<?php echo esc_attr( $test->ID ); ?>">
                        <?php esc_html_e( 'Ver resultado', 'club-psychology-pro' ); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <div id="cpp-result-container" style="margin-top:1em;"></div>
    <?php else : ?>
        <p><?php esc_html_e( 'No has solicitado ningún test aún.', 'club-psychology-pro' ); ?></p>
    <?php endif; ?>
</div>
