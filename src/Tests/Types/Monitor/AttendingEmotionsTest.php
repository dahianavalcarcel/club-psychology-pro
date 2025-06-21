<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\AttendingEmotionsCalculator;
use ClubPsychologyPro\Test\Validators\AttendingEmotionsValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * Escala de Atención a las Emociones (IPIP).
 */
class AttendingEmotionsTest extends AbstractTestType
{
    /**
     * AttendingEmotionsTest constructor.
     *
     * @param AttendingEmotionsCalculator $calculator     Calculadora de Atención a las Emociones
     * @param AttendingEmotionsValidator  $validator      Validador de respuestas
     * @param FormRenderer                $formRenderer   Renderizador del formulario
     * @param ResultRenderer              $resultRenderer Renderizador de resultados
     */
    public function __construct(
        AttendingEmotionsCalculator $calculator,
        AttendingEmotionsValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'attending_emotions',                // Slug único
            'Attending to Emotions (IPIP)',      // Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
