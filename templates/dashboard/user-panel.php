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
        // Formulario inline sin CSS styling
        ?>
        <div class="cpp-test-form-wrapper">
            <h3><?php esc_html_e( 'Solicitar Nuevo Test', 'club-psychology-pro' ); ?></h3>
            
            <?php if (isset($_GET['test_requested']) && $_GET['test_requested'] === 'success'): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e( 'Solicitud de test enviada correctamente. El enlace del test será enviado a tu email.', 'club-psychology-pro' ); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="cppTestRequestForm">
                <?php wp_nonce_field('test_form_submit', 'test_form_nonce'); ?>
                <input type="hidden" name="action" value="submit_test_request">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Tipo de Test', 'club-psychology-pro' ); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php esc_html_e( 'Selecciona el tipo de test', 'club-psychology-pro' ); ?></legend>
                                
                                <label>
                                    <input type="radio" name="test_type" value="b5ai" required>
                                    <strong><?php esc_html_e( 'Test B5 AI', 'club-psychology-pro' ); ?></strong><br>
                                    <em><?php esc_html_e( 'Evaluación completa de personalidad con IA', 'club-psychology-pro' ); ?></em>
                                </label><br><br>
                                
                                <label style="opacity: 0.5;">
                                    <input type="radio" name="test_type" value="desiderativo" disabled>
                                    <strong><?php esc_html_e( 'Desiderativo', 'club-psychology-pro' ); ?></strong><br>
                                    <em><?php esc_html_e( 'Próximamente', 'club-psychology-pro' ); ?></em>
                                </label><br><br>
                                
                                <label style="opacity: 0.5;">
                                    <input type="radio" name="test_type" value="cohesion" disabled>
                                    <strong><?php esc_html_e( 'Cohesión de Equipo', 'club-psychology-pro' ); ?></strong><br>
                                    <em><?php esc_html_e( 'Próximamente', 'club-psychology-pro' ); ?></em>
                                </label><br><br>
                                
                                <label>
                                    <input type="radio" name="test_type" value="monitor">
                                    <strong><?php esc_html_e( 'Test Monitor', 'club-psychology-pro' ); ?></strong><br>
                                    <em><?php esc_html_e( 'Evaluaciones especializadas', 'club-psychology-pro' ); ?></em>
                                </label>
                            </fieldset>
                            
                            <div id="cpp-monitor-tests" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                                <h4><?php esc_html_e( 'Selecciona Test Monitor:', 'club-psychology-pro' ); ?></h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                    <button type="button" class="button cpp-monitor-option" data-value="rebelliousness">REBELLIOUSNESS</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="likes-parties">Likes Parties</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="emotional-instability">EMOTIONAL INSTABILITY</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="belligerence">BELLIGERENCE</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="citizenship">Citizenship/Teamwork</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="industry">INDUSTRY</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="attending-emotions">ATTENDING TO EMOTIONS</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="docility">DOCILITY</button>
                                    <button type="button" class="button cpp-monitor-option" data-value="adaptability">ADAPTABILITY</button>
                                </div>
                                <input type="hidden" name="monitor_test" id="cpp-monitor-test-value">
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cpp_player_name"><?php esc_html_e( 'Nombre del Jugador', 'club-psychology-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cpp_player_name" name="player_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cpp_birth_date"><?php esc_html_e( 'Fecha de Nacimiento', 'club-psychology-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="date" id="cpp_birth_date" name="birth_date" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cpp_email"><?php esc_html_e( 'Email del Jugador', 'club-psychology-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cpp_email" name="email" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                
                <div class="notice notice-info inline">
                    <p>
                        <strong><?php esc_html_e( 'Información Importante:', 'club-psychology-pro' ); ?></strong><br>
                        <?php esc_html_e( 'El enlace del test será enviado automáticamente a la dirección de email proporcionada. Asegúrate de que la dirección sea correcta.', 'club-psychology-pro' ); ?>
                    </p>
                </div>
                
                <p class="submit">
                    <button type="button" class="button js-close-test-form"><?php esc_html_e( 'Cancelar', 'club-psychology-pro' ); ?></button>
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Crear Test', 'club-psychology-pro' ); ?>">
                </p>
            </form>
        </div>
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

<script>
jQuery(document).ready(function($) {
    // Abrir formulario de test
    $('.js-open-test-form').on('click', function() {
        $('#cpp-test-form-container').slideDown();
        $(this).hide();
    });
    
    // Cerrar formulario de test
    $('.js-close-test-form').on('click', function() {
        $('#cpp-test-form-container').slideUp();
        $('.js-open-test-form').show();
        // Limpiar formulario
        $('#cppTestRequestForm')[0].reset();
        $('#cpp-monitor-tests').hide();
        $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
        $('#cpp-monitor-test-value').val('');
    });
    
    // Manejar selección de tipo de test
    $('input[name="test_type"]').on('change', function() {
        if ($(this).val() === 'monitor') {
            $('#cpp-monitor-tests').slideDown();
        } else {
            $('#cpp-monitor-tests').slideUp();
            $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
            $('#cpp-monitor-test-value').val('');
        }
    });
    
    // Manejar selección de test monitor
    $('.cpp-monitor-option').on('click', function(e) {
        e.preventDefault();
        $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
        $(this).removeClass('button').addClass('button-primary');
        $('#cpp-monitor-test-value').val($(this).data('value'));
    });
    
    // Validación del formulario
    $('#cppTestRequestForm').on('submit', function(e) {
        var testType = $('input[name="test_type"]:checked').val();
        
        if (testType === 'monitor' && !$('#cpp-monitor-test-value').val()) {
            e.preventDefault();
            alert('<?php esc_html_e( "Por favor selecciona un test monitor específico.", "club-psychology-pro" ); ?>');
            return false;
        }
        
        // Estado de carga
        $(this).find('input[type="submit"]').prop('disabled', true).val('<?php esc_attr_e( "Creando Test...", "club-psychology-pro" ); ?>');
    });
    
    // Funcionalidad existente para ver resultados
    $('.cpp-btn-view-result').on('click', function() {
        var testId = $(this).data('id');
        // Aquí puedes agregar la lógica para mostrar el resultado
        $('#cpp-result-container').html('<p><?php esc_html_e( "Cargando resultado del test...", "club-psychology-pro" ); ?></p>');
    });
});
</script>