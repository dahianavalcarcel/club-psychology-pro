<?php
/**
 * Email Manager
 *
 * Se encarga de enviar invitaciones de test, recordatorios y notificaciones de resultados.
 *
 * @package ClubPsychologyPro\Email
 * @since   2.0.0
 */

namespace ClubPsychologyPro\Email;

use ClubPsychologyPro\Core\Plugin;
use ClubPsychologyPro\Database\DatabaseManager;
use ClubPsychologyPro\Email\TemplateEngine;
use Exception;

class EmailManager
{
    /** @var DatabaseManager */
    private DatabaseManager $db;

    /** @var TemplateEngine */
    private TemplateEngine $templates;

    /**
     * Constructor.
     *
     * @param Plugin $plugin Plugin principal para obtener dependencias.
     */
    public function __construct(Plugin $plugin)
    {
        $this->db        = $plugin->getContainer()->get(DatabaseManager::class);
        $this->templates = $plugin->getContainer()->get(TemplateEngine::class);
    }

    /**
     * Inicializa el EmailManager: registra hooks y cron jobs.
     *
     * @return void
     */
    public function init(): void
    {
        // Al completar un test, enviar notificación de resultado
        add_action('cpp_test_completed', [$this, 'onTestCompleted'], 10, 3);

        // AJAX para reenvío manual desde el admin
        add_action('wp_ajax_cpp_resend_test', [$this, 'handleAjaxResendTest']);

        // Cron job para enviar recordatorios pendientes
        add_action('cpp_send_reminders', [$this, 'sendPendingReminders']);
    }

    /**
     * Handler del hook cpp_test_completed: envía email de notificación de resultado.
     *
     * @param int $test_id
     * @param int $result_id
     * @param int $user_id
     * @return void
     */
    public function onTestCompleted(int $test_id, int $result_id, int $user_id): void
    {
        try {
            $this->sendResultNotification($result_id, $user_id);
        } catch (Exception $e) {
            error_log("EmailManager::onTestCompleted error: " . $e->getMessage());
        }
    }

    /**
     * Envía la notificación de resultado al usuario.
     *
     * @param int $result_id
     * @param int $user_id
     * @return void
     * @throws Exception
     */
    public function sendResultNotification(int $result_id, int $user_id): void
    {
        // Obtener datos de usuario y resultado
        $user   = get_userdata($user_id);
        $result = $this->db->selectOne(
            "SELECT * FROM {$this->db->getTableName('test_results')} WHERE id = %d",
            [ $result_id ]
        );

        if (! $user || ! $result) {
            throw new Exception("No se encontró usuario o resultado para envío de email.");
        }

        // Preparar asunto y cuerpo
        $subject = __("Tu resultado del test está listo", 'club-psychology-pro');
        $body    = $this->templates->render(
            'result-notification.php',
            [
                'user'   => $user,
                'result' => $result,
                'link'   => get_permalink(get_option('cpp_results_page_id')) . "?id={$result_id}",
            ]
        );

        // Enviar email
        wp_mail(
            $user->user_email,
            $subject,
            $body,
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );
    }

    /**
     * Reenvía la invitación o notificación de resultado vía AJAX.
     *
     * @return void
     */
    public function handleAjaxResendTest(): void
    {
        if (! current_user_can('manage_options') || empty($_POST['result_id'])) {
            wp_send_json_error('Permiso denegado o ID faltante');
        }

        $result_id = intval($_POST['result_id']);
        $row       = $this->db->selectOne(
            "SELECT user_id FROM {$this->db->getTableName('test_results')} WHERE id = %d",
            [ $result_id ]
        );

        try {
            $this->sendResultNotification($result_id, $row->user_id);
            wp_send_json_success('Email reenviado correctamente');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Envía recordatorios pendientes (cron job).
     *
     * @return void
     */
    public function sendPendingReminders(): void
    {
        $rows = $this->db->select(
            "SELECT i.id, i.user_id
             FROM {$this->db->getTableName('test_invitations')} i
             WHERE i.status = 'pending'
               AND i.reminder_at <= %s",
            [ current_time('mysql') ]
        );

        foreach ($rows as $invitation) {
            try {
                $this->sendTestInvitation(intval($invitation->id));
                // Marcar como enviado
                $this->db->update(
                    'test_invitations',
                    [ 'status' => 'sent', 'updated_at' => current_time('mysql') ],
                    [ 'id' => $invitation->id ]
                );
            } catch (Exception $e) {
                error_log("EmailManager::sendPendingReminders error: " . $e->getMessage());
            }
        }
    }

    /**
     * Envía la invitación a realizar el test al usuario.
     *
     * @param int $invitation_id
     * @return void
     * @throws Exception
     */
    public function sendTestInvitation(int $invitation_id): void
    {
        $invite = $this->db->selectOne(
            "SELECT * FROM {$this->db->getTableName('test_invitations')} WHERE id = %d",
            [ $invitation_id ]
        );

        if (! $invite) {
            throw new Exception("Invitación #{$invitation_id} no encontrada.");
        }

        $user = get_userdata($invite->user_id);
        if (! $user) {
            throw new Exception("Usuario #{$invite->user_id} no encontrado.");
        }

        $subject = __("Tienes un test pendiente", 'club-psychology-pro');
        $body    = $this->templates->render(
            'invitation.php',
            [
                'user'       => $user,
                'invitation' => $invite,
                'link'       => get_permalink(get_option('cpp_test_form_page_id')) . "?inv={$invitation_id}",
            ]
        );

        wp_mail(
            $user->user_email,
            $subject,
            $body,
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );
    }
}
