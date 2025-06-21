<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Validators;

use ClubPsychologyPro\Test\Interfaces\ValidatorInterface;

/**
 * Valida que ciertos campos obligatorios estén presentes y no estén vacíos.
 */
class RequiredFieldsValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * Lista de IDs de preguntas o campos que deben estar presentes.
     *
     * @var string[]
     */
    private array $requiredFields;

    /**
     * @param string[] $requiredFields
     */
    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    /**
     * {@inheritDoc}
     */
    protected function performValidation(array $responses): void
    {
        foreach ($this->requiredFields as $field) {
            if (
                ! array_key_exists($field, $responses)
                || $responses[$field] === null
                || (is_string($responses[$field]) && trim($responses[$field]) === '')
            ) {
                $this->addError(\sprintf(
                    'El campo "%s" es obligatorio.',
                    $field
                ));
            }
        }
    }
}
