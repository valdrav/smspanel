<?php

namespace App\DTOs\ActivityLog;

use App\DTOs\BaseData;
use App\Enums\ActivityAction;

/**
 * Aktivite log kaydı veri transfer nesnesi.
 */
readonly class CreateActivityLogData extends BaseData
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function __construct(
        public ActivityAction $action,
        public string $description,
        public ?int $userId = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?array $properties = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            action: ActivityAction::from((string) $data['action']),
            description: (string) $data['description'],
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            subjectType: isset($data['subject_type']) ? (string) $data['subject_type'] : null,
            subjectId: isset($data['subject_id']) ? (int) $data['subject_id'] : null,
            properties: isset($data['properties']) ? (array) $data['properties'] : null,
            ipAddress: isset($data['ip_address']) ? (string) $data['ip_address'] : null,
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null,
        );
    }
}
