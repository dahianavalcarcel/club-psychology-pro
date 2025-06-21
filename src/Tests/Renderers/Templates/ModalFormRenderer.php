<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use ClubPsychologyPro\Test\Interfaces\TestTypeInterface;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;

/**
 * Renderiza un formulario de test dentro de un modal.
 */
class ModalFormRenderer implements RendererInterface
{
    private FormRenderer $formRenderer;
    private string $modalIdPrefix;

    /**
     * @param FormRenderer $formRenderer  Instancia del renderer de formulario.
     * @param string       $modalIdPrefix Prefijo para el ID del modal (por si hay múltiples modales en la misma página).
     */
    public function __construct(FormRenderer $formRenderer, string $modalIdPrefix = 'cpp-modal-')
    {
        $this->formRenderer  = $formRenderer;
        $this->modalIdPrefix = $modalIdPrefix;
    }

    /**
     * {@inheritDoc}
     *
     * @param TestTypeInterface $test    El tipo de test a renderizar.
     * @param array             $options Opciones de renderizado. Permite 'button_text' para el texto del botón.
     * @return string HTML del botón y del modal con el formulario.
     */
    public function render(TestTypeInterface $test, array $options = []): string
    {
        // Generar un ID único para el modal basado en el tipo de test
        $modalId    = $this->modalIdPrefix . $test->getType();
        $buttonText = $options['button_text'] ?? __('Realizar Test', 'club-psychology-pro');
        // Renderizar el formulario usando el FormRenderer
        $formHtml   = $this->formRenderer->render($test, $options);

        ob_start();
        ?>
        <button type="button"
                class="cpp-modal-open-btn"
                data-target="#<?php echo esc_attr($modalId); ?>">
            <?php echo esc_html($buttonText); ?>
        </button>

        <div id="<?php echo esc_attr($modalId); ?>"
             class="cpp-modal"
             style="display:none;">
            <div class="cpp-modal-overlay"></div>
            <div class="cpp-modal-content">
                <button type="button" class="cpp-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'club-psychology-pro'); ?>">
                    &times;
                </button>
                <?php echo $formHtml; ?>
            </div>
        </div>

        <script>
        (function(){
            var btn   = document.querySelector('[data-target="#<?php echo esc_js($modalId); ?>"]');
            var modal = document.getElementById('<?php echo esc_js($modalId); ?>');
            if (!btn || !modal) return;

            var overlay = modal.querySelector('.cpp-modal-overlay');
            var close   = modal.querySelector('.cpp-modal-close');

            // Abrir modal
            btn.addEventListener('click', function(){
                modal.style.display = 'block';
            });

            // Cerrar modal al hacer clic en la 'X'
            close.addEventListener('click', function(){
                modal.style.display = 'none';
            });

            // Cerrar modal al hacer clic fuera del contenido
            overlay.addEventListener('click', function(){
                modal.style.display = 'none';
            });
        })();
        </script>
        <style>
        /* Estilos básicos para el modal */
        .cpp-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        .cpp-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }
        .cpp-modal-content {
            position: relative;
            background: #fff;
            max-width: 600px;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .cpp-modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.75rem;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        </style>
        <?php
        return (string) ob_get_clean();
    }
}
