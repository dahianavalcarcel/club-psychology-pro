<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types;

use ClubPsychologyPro\Calculators\BigFiveCalculator;
use ClubPsychologyPro\Test\Validators\BigFiveValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;

/**
 * Big Five (B5-AI) personality test type.
 */
class BigFiveTest extends AbstractTestType
{
    /**
     * BigFiveTest constructor.
     *
     * @param BigFiveCalculator $calculator     Calculadora de puntuaciones Big Five
     * @param BigFiveValidator  $validator      Validador de respuestas Big Five
     * @param FormRenderer      $formRenderer   Renderizador del formulario
     * @param ResultRenderer    $resultRenderer Renderizador del resultado
     */
    public function __construct(
        BigFiveCalculator $calculator,
        BigFiveValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'bigfive',                          // slug único del test
            'Big Five (B5-AI)',                 // etiqueta amigable
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
