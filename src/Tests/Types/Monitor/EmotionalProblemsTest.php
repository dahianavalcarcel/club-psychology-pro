<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\PhqSadsCalculator;
use ClubPsychologyPro\Test\Validators\PhqSadsValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * PHQ-SADS: Síntomas Somáticos, Ansiedad, Depresión y Pánico.
 */
class EmotionalProblemsTest extends AbstractTestType
{
    /**
     * EmotionalProblemsTest constructor.
     *
     * @param PhqSadsCalculator   $calculator     Calculadora PHQ-SADS
     * @param PhqSadsValidator    $validator      Validador de respuestas PHQ-SADS
     * @param FormRenderer        $formRenderer   Renderizador del formulario
     * @param ResultRenderer      $resultRenderer Renderizador de los resultados
     */
    public function __construct(
        PhqSadsCalculator $calculator,
        PhqSadsValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'emotional_problems',                           // Slug único
            'PHQ-SADS (Somáticos, Ansiedad, Depresión, Pánico)',  // Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
