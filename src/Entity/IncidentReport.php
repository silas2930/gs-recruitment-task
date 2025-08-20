<?php
namespace App\Entity;

final class IncidentReport
{
    public function __construct(
        public string $description,
        public string $type,
        public string $priority,
        public string $serviceVisitDate,
        public string $status,
        public string $serviceNotes,
        public string $phoneNumber,
        public string $creationDate
    ) {}

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'service_visit_date' => $this->serviceVisitDate,
            'status' => $this->status,
            'service_notes' => $this->serviceNotes,
            'phone_number' => $this->phoneNumber,
            'creation_date' => $this->creationDate,
        ];
    }
}
