<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types\Monitor;

use ClubPsychologyPro\Calculators\Who5WellbeingCalculator;
use ClubPsychologyPro\Test\Validators\Who5WellbeingValidator;
use ClubPsychologyPro\Test\Renderers\Templates\FormRenderer;
use ClubPsychologyPro\Test\Renderers\Templates\ResultRenderer;
use ClubPsychologyPro\Test\Types\AbstractTestType;

/**
 * WHO-5 Well-Being Index Test.
 */
class Who5WellbeingTest extends AbstractTestType
{
    /**
     * Who5WellbeingTest constructor.
     *
     * @param Who5WellbeingCalculator $calculator     Calculator for WHO-5 scoring
     * @param Who5WellbeingValidator  $validator      Validator for WHO-5 responses
     * @param FormRenderer            $formRenderer   Renders the test form
     * @param ResultRenderer          $resultRenderer Renders the test result
     */
    public function __construct(
        Who5WellbeingCalculator $calculator,
        Who5WellbeingValidator $validator,
        FormRenderer $formRenderer,
        ResultRenderer $resultRenderer
    ) {
        parent::__construct(
            'who5_wellbeing',           // Unique slug
            'WHO-5 Well-Being Index',   // Human-readable name
            $calculator,
            $validator,
            $formRenderer,
            $resultRenderer
        );
    }
}
