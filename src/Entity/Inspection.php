<?php
namespace App\Entity;

final class Inspection
{
    public function __construct(
        public string $description,
        public string $type,
        public string $inspectionDate,
        public string $weekOfYear,
        public string $status,
        public string $followUpRecommendations,
        public string $phoneNumber,
        public string $creationDate
    ) {}

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'type' => $this->type,
            'inspection_date' => $this->inspectionDate,
            'week_of_year' => $this->weekOfYear,
            'status' => $this->status,
            'follow_up_recommendations' => $this->followUpRecommendations,
            'phone_number' => $this->phoneNumber,
            'creation_date' => $this->creationDate,
        ];
    }
}
