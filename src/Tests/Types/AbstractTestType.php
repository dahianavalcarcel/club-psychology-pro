<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Types;

use ClubPsychologyPro\Test\Interfaces\TestTypeInterface;
use ClubPsychologyPro\Test\Interfaces\CalculatorInterface;
use ClubPsychologyPro\Test\Interfaces\ValidatorInterface;
use ClubPsychologyPro\Test\Interfaces\RendererInterface;

abstract class AbstractTestType implements TestTypeInterface
{
    protected string $type;
    protected string $label;
    protected CalculatorInterface $calculator;
    protected ValidatorInterface $validator;
    protected RendererInterface $formRenderer;
    protected RendererInterface $resultRenderer;

    /**
     * AbstractTestType constructor.
     *
     * @param string             $type           Slug único del test (p.ej. "anger_rumination")
     * @param string             $label          Nombre amigable (p.ej. "Rumiación de la Ira")
     * @param CalculatorInterface $calculator    Lógica de cálculo de puntuaciones
     * @param ValidatorInterface  $validator     Lógica de validación de respuestas
     * @param RendererInterface   $formRenderer  Renderizador del formulario
     * @param RendererInterface   $resultRenderer Renderizador del resultado
     */
    public function __construct(
        string $type,
        string $label,
        CalculatorInterface $calculator,
        ValidatorInterface $validator,
        RendererInterface $formRenderer,
        RendererInterface $resultRenderer
    ) {
        $this->type            = $type;
        $this->label           = $label;
        $this->calculator      = $calculator;
        $this->validator       = $validator;
        $this->formRenderer    = $formRenderer;
        $this->resultRenderer  = $resultRenderer;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function renderForm(array $context = []): string
    {
        return $this->formRenderer->render($context);
    }

    /**
     * @inheritDoc
     */
    public function renderResult(array $context = []): string
    {
        return $this->resultRenderer->render($context);
    }

    /**
     * Valida las respuestas y devuelve un array de errores, si los hay.
     *
     * @param array $responses
     * @return array<string,string> Mapa campo => mensaje de error
     */
    public function validateResponses(array $responses): array
    {
        return $this->validator->validate($responses);
    }

    /**
     * Procesa un conjunto de respuestas: primero valida, luego calcula.
     * Si hay errores de validación, devuelve ['errors' => [...]].
     *
     * @param array $responses
     * @return array Resultado de cálculo o errores
     */
    public function process(array $responses): array
    {
        $errors = $this->validateResponses($responses);
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return $this->calculator->calculate($responses);
    }
}
