<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use ClubPsychologyPro\Test\Interfaces\TestTypeInterface;

/**
 * Renderiza un formulario de test dividido en secciones.
 */
class SectionedFormRenderer implements RendererInterface
{
    /**
     * {@inheritDoc}
     *
     * Espera que el TestTypeInterface devuelva un arreglo de secciones con:
     *  - 'title'       => string
     *  - 'description' => string (opcional)
     *  - 'questions'   => array de preguntas, cada una con:
     *      - 'id'      => string
     *      - 'text'    => string
     *      - 'options' => [ value => label, ... ]
     *
     * @param TestTypeInterface $test    El tipo de test a renderizar.
     * @param array             $options Opciones de renderizado:
     *                                   - 'action'      => URL donde enviar el formulario.
     *                                   - 'method'      => 'post' o 'get'.
     *                                   - 'submit_text' => Texto del botón de envío.
     * @return string HTML del formulario con secciones.
     */
    public function render(TestTypeInterface $test, array $options = []): string
    {
        $sections  = $test->getSections();
        $action    = $options['action']      ?? '';
        $method    = $options['method']      ?? 'post';
        $submitTxt = $options['submit_text'] ?? __('Enviar', 'club-psychology-pro');

        ob_start();
        ?>
        <form action="<?php echo esc_url($action); ?>" method="<?php echo esc_attr($method); ?>" class="cpp-sectioned-form">
            <?php foreach ($sections as $sectionId => $section): ?>
                <fieldset id="cpp-section-<?php echo esc_attr($sectionId); ?>" class="cpp-form-section">
                    <?php if (!empty($section['title'])): ?>
                        <legend class="cpp-section-title"><?php echo esc_html($section['title']); ?></legend>
                    <?php endif; ?>
                    <?php if (!empty($section['description'])): ?>
                        <p class="cpp-section-description"><?php echo esc_html($section['description']); ?></p>
                    <?php endif; ?>

                    <?php foreach ($section['questions'] as $question): 
                        $qid  = $question['id'];
                        $text = $question['text'];
                    ?>
                        <div class="cpp-form-question">
                            <p class="cpp-question-text"><?php echo esc_html($text); ?></p>
                            <div class="cpp-question-options">
                                <?php foreach ($question['options'] as $value => $label): 
                                    $inputId = $qid . '_' . $value;
                                ?>
                                    <label for="<?php echo esc_attr($inputId); ?>" class="cpp-option-label">
                                        <input 
                                            type="radio" 
                                            name="<?php echo esc_attr($qid); ?>" 
                                            id="<?php echo esc_attr($inputId); ?>" 
                                            value="<?php echo esc_attr($value); ?>" 
                                            required
                                        >
                                        <?php echo esc_html($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>

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
