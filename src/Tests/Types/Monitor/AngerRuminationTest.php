<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\AngerRuminationCalculator;
use ClubPsychologyPro\Test\Validators\AngerRuminationValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * Test de Rumiación de la Ira (ARS).
 */
class AngerRuminationTest extends AbstractTestType
{
    /**
     * AngerRuminationTest constructor.
     *
     * @param AngerRuminationCalculator $calculator     Calculadora ARS
     * @param AngerRuminationValidator  $validator      Validador de respuestas ARS
     * @param FormRenderer              $formRenderer   Renderizador del formulario
     * @param ResultRenderer            $resultRenderer Renderizador de los resultados
     */
    public function __construct(
        AngerRuminationCalculator $calculator,
        AngerRuminationValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'anger_rumination',                 // Slug único
            'Anger Rumination (ARS)',           // Nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
