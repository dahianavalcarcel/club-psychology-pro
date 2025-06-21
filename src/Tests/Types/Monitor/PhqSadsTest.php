<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\PhqSadsCalculator;
use ClubPsychologyPro\Test\Validators\PhqSadsValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * PHQ-SADS Test (Somáticos, Ansiedad, Depresión y Pánico).
 */
class PhqSadsTest extends AbstractTestType
{
    /**
     * PhqSadsTest constructor.
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
            'phq_sads',                  // Slug único del test
            'PHQ-SADS',                  // Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
