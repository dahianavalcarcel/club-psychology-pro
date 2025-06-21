<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test;

use WP_Post;

/**
 * Class ResultManager
 *
 * Maneja el almacenamiento y recuperación de resultados de tests.
 */
class ResultManager
{
    /**
     * El slug del Custom Post Type donde se guardan los resultados.
     */
    private string $postType = 'cpp_result';

    public function __construct()
    {
        // Registrar el CPT en la inicialización de WordPress
        add_action('init', [$this, 'registerPostType'], 5);
    }

    /**
     * Registra el Custom Post Type para resultados.
     */
    public function registerPostType(): void
    {
        register_post_type($this->postType, [
            'labels'             => [
                'name'               => __('Resultados', 'club-psychology-pro'),
                'singular_name'      => __('Resultado', 'club-psychology-pro'),
                'add_new'            => __('Añadir Resultado', 'club-psychology-pro'),
                'add_new_item'       => __('Añadir Nuevo Resultado', 'club-psychology-pro'),
                'edit_item'          => __('Editar Resultado', 'club-psychology-pro'),
                'view_item'          => __('Ver Resultado', 'club-psychology-pro'),
                'search_items'       => __('Buscar Resultados', 'club-psychology-pro'),
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => ['title', 'custom-fields'],
            'has_archive'        => false,
        ]);
    }

    /**
     * Guarda un nuevo resultado de test.
     *
     * @param int    $testId     ID del test que se ejecutó.
     * @param int    $userId     ID del usuario que lo completó.
     * @param string $testType   Clave del tipo de test.
     * @param array  $resultData Datos a almacenar como meta.
     *
     * @return int El ID del post de resultado creado.
     * @throws \RuntimeException Si ocurre un error al insertar el post.
     */
    public function saveResult(int $testId, int $userId, string $testType, array $resultData): int
    {
        $postArr = [
            'post_type'   => $this->postType,
            'post_title'  => sprintf('%s — %s', $testType, date_i18n('Y-m-d H:i')),
            'post_status' => 'publish',
            'post_author' => $userId,
        ];

        $resultId = wp_insert_post($postArr, true);
        if (is_wp_error($resultId)) {
            throw new \RuntimeException(sprintf(
                'Error al guardar resultado: %s',
                $resultId->get_error_message()
            ));
        }

        // Metadatos básicos
        update_post_meta($resultId, 'cpp_test_id', $testId);
        update_post_meta($resultId, 'cpp_test_type', $testType);

        // Metadatos específicos del resultado
        foreach ($resultData as $key => $value) {
            update_post_meta($resultId, $key, $value);
        }

        return $resultId;
    }

    /**
     * Recupera un resultado por su ID.
     *
     * @param int $resultId
     * @return WP_Post|null
     */
    public function getResult(int $resultId): ?WP_Post
    {
        $post = get_post($resultId);
        if (!$post || $post->post_type !== $this->postType) {
            return null;
        }
        return $post;
    }

    /**
     * Obtiene todos los resultados de un usuario, opcionalmente filtrados por tipo de test.
     *
     * @param int         $userId
     * @param string|null $testType
     * @return WP_Post[]
     */
    public function getResultsByUser(int $userId, ?string $testType = null): array
    {
        $args = [
            'post_type'      => $this->postType,
            'author'         => $userId,
            'posts_per_page' => -1,
        ];

        if ($testType) {
            $args['meta_query'] = [
                [
                    'key'     => 'cpp_test_type',
                    'value'   => $testType,
                    'compare' => '=',
                ],
            ];
        }

        return get_posts($args);
    }

    /**
     * Elimina un resultado.
     *
     * @param int  $resultId
     * @param bool $forceDelete Si es true, lo borra permanentemente.
     * @return bool
     */
    public function deleteResult(int $resultId, bool $forceDelete = false): bool
    {
        return (bool) wp_delete_post($resultId, $forceDelete);
    }
}
