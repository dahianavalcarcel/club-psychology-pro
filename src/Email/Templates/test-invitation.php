<?php
namespace ClubPsychologyPro\Email;

use ClubPsychologyPro\Core\ConfigManager;
use ClubPsychologyPro\Core\Container;
use WP_Error;

/**
 * Class EmailManager
 *
 * Responsable de enviar correos electrónicos usando plantillas.
 */
class EmailManager
{
    /**
     * @var ConfigManager
     */
    protected ConfigManager $config;

    /**
     * EmailManager constructor.
     *
     * @param ConfigManager $config
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * Envía un correo usando una plantilla.
     *
     * @param string       $to        Dirección de destino.
     * @param string       $subject   Asunto del correo.
     * @param string       $template  Nombre de la plantilla (sin extensión).
     * @param array        $data      Variables para popular la plantilla.
     * @param string|null  $fromEmail Dirección "from" opcional. Si es null, toma de config.
     * @param string|null  $fromName  Nombre "from" opcional. Si es null, toma de config.
     *
     * @return true|WP_Error  True si fue exitoso, WP_Error en caso de fallo.
     */
    public function send(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        // Obtener configuración por defecto
        $emailConfig = $this->config->get('email');
        $fromEmail = $fromEmail ?? ($emailConfig['from_address'] ?? get_option('admin_email'));
        $fromName  = $fromName  ?? ($emailConfig['from_name']    ?? get_bloginfo('name'));

        // Preparar cabeceras
        $headers   = [
            sprintf('From: %s <%s>', wp_strip_all_tags($fromName), sanitize_email($fromEmail)),
            'Content-Type: text/html; charset=UTF-8',
        ];

        /**
         * Permite modificar datos antes de enviar el email.
         *
         * @param array  $args ['to','subject','template','data','headers']
         */
        $args = apply_filters('cpp_before_send_email', [
            'to'       => $to,
            'subject'  => $subject,
            'template' => $template,
            'data'     => $data,
            'headers'  => $headers,
        ]);

        // Renderizar cuerpo
        $body = $this->renderTemplate($args['template'], $args['data']);
        if ( is_wp_error($body) ) {
            return $body;
        }

        // Enviar correo
        $result = wp_mail(
            $args['to'],
            wp_strip_all_tags($args['subject']),
            $body,
            $args['headers']
        );

        /**
         * Acción tras intentar enviar el correo.
         *
         * @param bool|WP_Error $result Resultado de wp_mail.
         * @param array         $args   Mismos argumentos enviados.
         */
        do_action('cpp_after_send_email', $result, $args);

        if ( false === $result ) {
            return new WP_Error('email_send_failed', __('Error al enviar el correo.', 'club-psychology-pro'));
        }

        return true;
    }

    /**
     * Renderiza una plantilla de email con los datos proporcionados.
     *
     * @param string $template Nombre de archivo en templates/email/ (sin .php).
     * @param array  $data     Variables que estarán disponibles en la plantilla.
     *
     * @return string|WP_Error Contenido HTML o WP_Error.
     */
    protected function renderTemplate(string $template, array $data)
    {
        $templatesDir = Container::getInstance()
            ->get('paths')['plugin_dir'] . '/templates/email';

        $templateFile = "{$templatesDir}/{$template}.php";
        if (! file_exists($templateFile)) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Plantilla de email "%s" no encontrada.', 'club-psychology-pro'), $template)
            );
        }

        // Extraer variables para la plantilla
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            return new WP_Error('template_error', $e->getMessage());
        }

        return ob_get_clean();
    }
}
