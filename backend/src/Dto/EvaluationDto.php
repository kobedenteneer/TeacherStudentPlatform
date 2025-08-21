<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EvaluationDto
{
    #[Assert\NotNull(message: 'Result is required')]
    #[Assert\Range(
        min: -1,
        max: 10,
        notInRangeMessage: 'Result must be between -1 and 10'
    )]
    public ?float $result = null;

    #[Assert\NotNull(message: 'Weight is required')]
    #[Assert\Range(
        min: 1,
        max: 19,
        notInRangeMessage: 'Weight must be between 1 and 19'
    )]
    public ?int $weight = null;

    #[Assert\Length(
        max: 1000,
        maxMessage: 'Message cannot be longer than 1000 characters'
    )]
    public ?string $message = null;

    public function __construct(?float $result = null, ?int $weight = null, ?string $message = null)
    {
        $this->result = $result;
        $this->weight = $weight;
        $this->message = $message;
    }
}
