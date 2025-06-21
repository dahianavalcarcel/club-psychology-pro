<?php
namespace ClubPsychologyPro\Email;

use WP_Error;

/**
 * Class ReminderSender
 *
 * Envía recordatorios por email a usuarios sobre tests pendientes o próximos vencimientos.
 */
class ReminderSender
{
    protected EmailManager $mailer;
    protected TemplateEngine $templates;

    /**
     * ReminderSender constructor.
     *
     * @param EmailManager    $mailer    Servicio de envío de emails.
     * @param TemplateEngine  $templates Motor de plantillas para emails.
     */
    public function __construct(EmailManager $mailer, TemplateEngine $templates)
    {
        $this->mailer    = $mailer;
        $this->templates = $templates;
    }

    /**
     * Envía un recordatorio de test al usuario.
     *
     * @param string      $toEmail      Dirección de correo del destinatario.
     * @param string      $userName     Nombre del usuario.
     * @param string      $testName     Nombre del test.
     * @param \DateTime   $dueDate      Fecha y hora límite para completar el test.
     * @param string|null $fromEmail    (opcional) Remitente. Si no se provee, usa el por defecto.
     *
     * @return true|WP_Error            true si se envió correctamente, WP_Error en caso de fallo.
     */
    public function sendReminder(
        string $toEmail,
        string $userName,
        string $testName,
        \DateTime $dueDate,
        ?string $fromEmail = null
    ) {
        // Renderiza el cuerpo del email
        $body = $this->templates->render('test-reminder', [
            'userName'  => $userName,
            'testName'  => $testName,
            'dueDate'   => $dueDate->format('d/m/Y H:i'),
        ]);

        if (is_wp_error($body)) {
            return $body;
        }

        $subject = sprintf(
            /* translators: %s: test name */
            __('Recordatorio: pendiente completar el test "%s"', 'club-psychology-pro'),
            $testName
        );

        // Prepara headers
        $headers = [];
        if ($fromEmail) {
            $headers[] = 'From: ' . $fromEmail;
        }

        // Envía el correo
        $sent = $this->mailer->send($toEmail, $subject, $body, $headers);

        if (is_wp_error($sent)) {
            return $sent;
        }

        return true;
    }
}
