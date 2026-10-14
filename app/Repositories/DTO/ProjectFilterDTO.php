<?php

declare(strict_types=1);

namespace App\Repositories\DTO;

use App\Enums\ProjectStatus;

final readonly class ProjectFilterDTO
{
    private function __construct(
        public ?string $text = null,
        public ?ProjectStatus $status = null,
        public ?string $orderByColumnName = null,
        public ?string $orderByColumnDirection = null,
    ) {}

    public static function create(
        ?string $text = null,
        ?ProjectStatus $status = null,
        ?string $orderByColumnName = null,
        ?string $orderByColumnDirection = null,
    ): self {
        return new self(
            text: $text,
            status: $status,
            orderByColumnName: $orderByColumnName,
            orderByColumnDirection: $orderByColumnDirection,
        );
    }
}
