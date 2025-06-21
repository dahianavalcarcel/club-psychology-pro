<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use ClubPsychologyPro\Test\Interfaces\TestTypeInterface;

/**
 * Renderiza un formulario de test en formato tabla.
 */
class TableFormRenderer implements RendererInterface
{
    /**
     * {@inheritDoc}
     *
     * Espera que TestTypeInterface devuelva:
     *  - getQuestions(): array de preguntas, cada una con:
     *      - 'id'      => string
     *      - 'text'    => string
     *      - 'options' => [ value => label, ... ]
     *
     * Todas las preguntas deben compartir el mismo conjunto de opciones.
     *
     * @param TestTypeInterface $test    El test a renderizar.
     * @param array             $options Opciones de renderizado:
     *                                   - 'action'      => URL donde enviar.
     *                                   - 'method'      => 'post'|'get'.
     *                                   - 'submit_text' => Texto del botón.
     * @return string HTML de un <form> con una <table>.
     */
    public function render(TestTypeInterface $test, array $options = []): string
    {
        $questions = $test->getQuestions();
        if (empty($questions)) {
            return '<p>' . esc_html__('No questions configured.', 'club-psychology-pro') . '</p>';
        }

        // Tomamos las opciones de la primera pregunta
        $firstOptions = $questions[0]['options'];

        $action    = $options['action']      ?? '';
        $method    = $options['method']      ?? 'post';
        $submitTxt = $options['submit_text'] ?? __('Enviar', 'club-psychology-pro');

        ob_start();
        ?>
        <form action="<?php echo esc_url($action); ?>" method="<?php echo esc_attr($method); ?>" class="cpp-table-form">
            <table class="cpp-form-table">
                <thead>
                    <tr>
                        <th class="cpp-table-header-question"><?php echo esc_html__('Pregunta', 'club-psychology-pro'); ?></th>
                        <?php foreach ($firstOptions as $optValue => $optLabel): ?>
                            <th class="cpp-table-header-option"><?php echo esc_html($optLabel); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question): 
                        $qid = $question['id'];
                    ?>
                        <tr class="cpp-form-row">
                            <td class="cpp-table-question-cell">
                                <?php echo esc_html($question['text']); ?>
                            </td>
                            <?php foreach ($firstOptions as $optValue => $optLabel): 
                                $inputId = "{$qid}_{$optValue}";
                            ?>
                                <td class="cpp-table-option-cell">
                                    <input
                                        type="radio"
                                        name="<?php echo esc_attr($qid); ?>"
                                        value="<?php echo esc_attr($optValue); ?>"
                                        id="<?php echo esc_attr($inputId); ?>"
                                        required
                                    >
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cpp-form-actions">
                <button type="submit" class="cpp-form-submit-btn">
                    <?php echo esc_html($submitTxt); ?>
                </button>
            </div>
        </form>
        <?php
        return (string) ob_get_clean();
    }
}
