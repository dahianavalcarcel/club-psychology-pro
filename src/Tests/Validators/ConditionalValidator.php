<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Validators;

use ClubPsychologyPro\Test\Interfaces\ValidatorInterface;

/**
 * Validates that certain responses are provided only when specified conditional requirements are met.
 */
class ConditionalValidator extends AbstractValidator
{
    /**
     * Map of question IDs to their conditional requirements.
     *
     * Each entry should be:
     *   'question_id' => [
     *       'depends_on' => 'other_question_id',
     *       'value'      => mixed,           // single expected value
     *       // optional custom error message:
     *       'message'    => 'Custom error text'
     *   ],
     *
     * @var array<string,array{depends_on:string,value:mixed,message?:string}>
     */
    private array $conditions;

    /**
     * @param array<string,array{depends_on:string,value:mixed,message?:string}> $conditions
     */
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * {@inheritDoc}
     */
    protected function performValidation(array $responses): void
    {
        foreach ($this->conditions as $questionId => $cond) {
            $dependsOn = $cond['depends_on'];
            $expected  = $cond['value'];

            // Only apply when the dependency is met
            if (
                array_key_exists($dependsOn, $responses)
                && $responses[$dependsOn] === $expected
            ) {
                // Then the target question must be present and non-empty
                if (
                    ! array_key_exists($questionId, $responses)
                    || $responses[$questionId] === '' 
                    || $responses[$questionId] === null
                ) {
                    $message = $cond['message']
                        ?? \sprintf(
                            'La pregunta "%s" es obligatoria cuando "%s" es "%s".',
                            $questionId,
                            $dependsOn,
                            (string) $expected
                        );
                    $this->addError($message);
                }
            }
        }
    }
}
