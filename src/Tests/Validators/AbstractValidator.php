<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Validators;

use ClubPsychologyPro\Test\Interfaces\ValidatorInterface;

/**
 * Base class for all test validators.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Collection of validation error messages.
     *
     * @var string[]
     */
    protected array $errors = [];

    /**
     * Runs the validation logic against the given responses.
     *
     * @param mixed[] $responses
     * @return bool True if validation passed, false otherwise.
     */
    public function validate(array $responses): bool
    {
        $this->errors = [];
        $this->performValidation($responses);

        return empty($this->errors);
    }

    /**
     * Returns any validation errors that occurred.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Adds a single error message to the collection.
     *
     * @param string $message
     * @return void
     */
    protected function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Concrete validators must implement their own validation rules here.
     *
     * @param mixed[] $responses
     * @return void
     */
    abstract protected function performValidation(array $responses): void;
}
