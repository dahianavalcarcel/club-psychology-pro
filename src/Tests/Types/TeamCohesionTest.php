<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types;

use ClubPsychologyPro\Calculators\TeamCohesionCalculator;
use ClubPsychologyPro\Test\Validators\TeamCohesionValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;

/**
 * Test de Cohesión de Equipo (GEQ).
 */
class TeamCohesionTest extends AbstractTestType
{
    /**
     * TeamCohesionTest constructor.
     *
     * @param TeamCohesionCalculator $calculator     Calculadora de cohesión de equipo
     * @param TeamCohesionValidator  $validator      Validador de respuestas GEQ
     * @param FormRenderer           $formRenderer   Renderizador del formulario
     * @param ResultRenderer         $resultRenderer Renderizador de resultados
     */
    public function __construct(
        TeamCohesionCalculator $calculator,
        TeamCohesionValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'team_cohesion',          // slug único del test
            'Team Cohesion (GEQ)',    // nombre legible
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
