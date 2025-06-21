<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Validators;

use ClubPsychologyPro\Test\Interfaces\ValidatorInterface;

/**
 * Validates that numeric responses fall within configured min‐max ranges.
 */
class RangeValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * Map of question IDs to their allowed min/max range.
     *
     * Each entry should be:
     *   'question_id' => [
     *       'min'     => float|int,
     *       'max'     => float|int,
     *       // optional custom error message:
     *       'message' => 'Custom error text'
     *   ],
     *
     * @var array<string,array{min:float|int,max:float|int,message?:string}>
     */
    private array $ranges;

    /**
     * @param array<string,array{min:float|int,max:float|int,message?:string}> $ranges
     */
    public function __construct(array $ranges)
    {
        $this->ranges = $ranges;
    }

    /**
     * {@inheritDoc}
     */
    protected function performValidation(array $responses): void
    {
        foreach ($this->ranges as $questionId => $conf) {
            if (! array_key_exists($questionId, $responses)) {
                // no response = skip (requiredness is handled by another validator)
                continue;
            }

            $value = $responses[$questionId];

            // Must be numeric
            if (! is_numeric($value)) {
                $this->addError(
                    \sprintf(
                        'La respuesta de "%s" debe ser un número.',
                        $questionId
                    )
                );
                continue;
            }

            $numeric = (float) $value;
            $min = (float) $conf['min'];
            $max = (float) $conf['max'];

            if ($numeric < $min || $numeric > $max) {
                $message = $conf['message']
                    ?? \sprintf(
                        'La respuesta de "%s" debe estar entre %s y %s.',
                        $questionId,
                        (string) $min,
                        (string) $max
                    );
                $this->addError($message);
            }
        }
    }
}
