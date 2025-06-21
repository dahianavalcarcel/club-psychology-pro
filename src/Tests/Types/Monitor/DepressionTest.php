<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\Who5WellbeingCalculator;
use ClubPsychologyPro\Test\Validators\Who5WellbeingValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * WHO-5 Well-Being Index (Depression/Bienestar).
 */
class DepressionTest extends AbstractTestType
{
    /**
     * DepressionTest constructor.
     *
     * @param Who5WellbeingCalculator $calculator     Calculadora WHO-5
     * @param Who5WellbeingValidator  $validator      Validador de respuestas
     * @param FormRenderer            $formRenderer   Renderizador del formulario
     * @param ResultRenderer          $resultRenderer Renderizador de resultados
     */
    public function __construct(
        Who5WellbeingCalculator $calculator,
        Who5WellbeingValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'depression_who5',             // Slug único
            'WHO-5 Well-Being Index',      // Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
