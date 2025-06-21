<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\SuggestibilityCalculator;
use ClubPsychologyPro\Test\Validators\SuggestibilityValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * Suggestibility Test (Short Suggestibility Scale - SSS).
 */
class SuggestibilityTest extends AbstractTestType
{
    /**
     * SuggestibilityTest constructor.
     *
     * @param SuggestibilityCalculator $calculator     Calculadora de sugestionabilidad
     * @param SuggestibilityValidator  $validator      Validador de respuestas SSS
     * @param FormRenderer             $formRenderer   Renderizador del formulario
     * @param ResultRenderer           $resultRenderer Renderizador de resultados
     */
    public function __construct(
        SuggestibilityCalculator $calculator,
        SuggestibilityValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'suggestibility',            // Slug único del test
            'Suggestibility Scale (SSS)',// Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
