<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test;

use ClubPsychologyPro\Tests\AngerRuminationTest;
use ClubPsychologyPro\Tests\AnxietyDepressionTest;
use ClubPsychologyPro\Tests\SuggestibilityTest;
use ClubPsychologyPro\Tests\WellbeingTest;
use ClubPsychologyPro\Tests\AttendingEmotionsTest;
use InvalidArgumentException;

/**
 * Class TestFactory
 *
 * Crea instancias de tests según su clave.
 */
class TestFactory
{
    /**
     * Mapa de claves de test a sus clases correspondientes.
     *
     * @var string[]
     */
    private array $registry = [
        'anger_rumination'    => AngerRuminationTest::class,
        'phq_sads'            => AnxietyDepressionTest::class,
        'suggestibility'      => SuggestibilityTest::class,
        'who_5'               => WellbeingTest::class,
        'attending_emotions'  => AttendingEmotionsTest::class,
    ];

    /**
     * Crea una instancia del test indicado.
     *
     * @param string $type   Clave única del test (debe coincidir con el registro).
     * @param array  $config Configuración específica para el test.
     *
     * @return TestInterface
     *
     * @throws InvalidArgumentException Si el tipo no está registrado.
     */
    public function create(string $type, array $config = []): TestInterface
    {
        if (!isset($this->registry[$type])) {
            throw new InvalidArgumentException(sprintf(
                'Tipo de test desconocido "%s". Claves disponibles: %s',
                $type,
                implode(', ', array_keys($this->registry))
            ));
        }

        $class = $this->registry[$type];

        /** @var TestInterface $instance */
        $instance = new $class($config);

        return $instance;
    }
}
