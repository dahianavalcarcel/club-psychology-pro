<?php
declare(strict_types=1);

return [

    // === Tests de Personalidad ===

    'bigfive' => [
        'title'       => 'BigFive (B5-AI)',
        'description' => 'Evaluación completa de personalidad: 5 dominios + 30 facetas',
        'class'       => \ClubPsychologyPro\Tests\Personalidad\BigFiveTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Personalidad\BigFiveCalculator::class,
        'template'    => 'tests/personalidad/bigfive.php',
        'time_limit'  => 1200, // 20 minutos
    ],

    'cohesion' => [
        'title'       => 'Cohesión de Equipo (GEQ)',
        'description' => 'Dinámica grupal y cohesión: 4 dimensiones',
        'class'       => \ClubPsychologyPro\Tests\Equipo\CohesionEquipoTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Equipo\CohesionEquipoCalculator::class,
        'template'    => 'tests/equipo/cohesion.php',
        'time_limit'  => 900, // 15 minutos
    ],


    // === Monitor Tests ===

    'anger_rumination' => [
        'title'       => 'Escala de Rumiación de la Ira (ARS)',
        'description' => 'Evalúa la tendencia a enfocarse en pensamientos de ira pasados',
        'class'       => \ClubPsychologyPro\Tests\Monitor\AngerRuminationTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Monitor\AngerRuminationCalculator::class,
        'template'    => 'tests/monitor/anger_rumination.php',
        'time_limit'  => 600, // 10 minutos
    ],

    'phq_sads' => [
        'title'       => 'Patient Health Questionnaire (PHQ-SADS)',
        'description' => 'Screening integral de síntomas somáticos, ansiedad, depresión y pánico',
        'class'       => \ClubPsychologyPro\Tests\Monitor\PHQSADSTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Monitor\PHQSADSCalculator::class,
        'template'    => 'tests/monitor/phq_sads.php',
        'time_limit'  => 900, // 15 minutos
    ],

    'miss' => [
        'title'       => 'Short Suggestibility Scale (MISS)',
        'description' => 'Evalúa tu nivel de susceptibilidad a la sugestión',
        'class'       => \ClubPsychologyPro\Tests\Monitor\SuggestionTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Monitor\SuggestionCalculator::class,
        'template'    => 'tests/monitor/miss.php',
        'time_limit'  => 720, // 12 minutos
    ],

    'who5' => [
        'title'       => 'Test de Bienestar (WHO-5)',
        'description' => 'Índice de bienestar de la Organización Mundial de la Salud',
        'class'       => \ClubPsychologyPro\Tests\Monitor\WellbeingTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Monitor\WellbeingCalculator::class,
        'template'    => 'tests/monitor/who5.php',
        'time_limit'  => 300, // 5 minutos
    ],

    'attending_emotions' => [
        'title'       => 'Escala de Atención a las Emociones (IPIP)',
        'description' => 'Mide tu conciencia y monitoreo de estados emocionales internos',
        'class'       => \ClubPsychologyPro\Tests\Monitor\AttendingEmotionsTest::class,
        'calculator'  => \ClubPsychologyPro\Tests\Monitor\AttendingEmotionsCalculator::class,
        'template'    => 'tests/monitor/attending_emotions.php',
        'time_limit'  => 420, // 7 minutos
    ],

];
