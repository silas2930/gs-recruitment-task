<?php
namespace App\Service;

use App\Entity\Inspection;
use App\Entity\IncidentReport;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

final class MessageProcessor
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array{inspections: array<int, Inspection>, incidentReports: array<int, IncidentReport>, unprocessable: array<int, array<string, mixed>>}
     */
    public function process(array $messages): array
    {
        $this->logger->info('Starting processing', ['total' => count($messages)]);
        $seen = [];
        $inspections = [];
        $incidents = [];
        $unprocessable = [];

        foreach ($messages as $msg) {
            $desc = trim((string)($msg['description'] ?? ''));
            $phone = (string)($msg['phone'] ?? '');
            $dueDateStr = $msg['dueDate'] ?? null;

            if ($desc === '') {
                $this->logger->warning('Skipping empty description', ['message' => $msg]);
                $unprocessable[] = $msg;
                continue;
            }
            if (in_array($desc, $seen, true)) {
                $this->logger->info('Duplicate message detected; skipping', ['description' => $desc]);
                $unprocessable[] = $msg;
                continue;
            }
            $seen[] = $desc;

            $isInspection = str_contains(strtolower($desc), 'inspection');
            $dt = $this->parseDate($dueDateStr);

            if ($isInspection) {
                $inspectionDate = $dt ? $dt->format('Y-m-d') : '';
                $week = $dt ? $dt->format('W') : '';
                $status = $dt ? 'scheduled' : 'new';

                $inspections[] = new Inspection(
                    description: $desc,
                    type: 'inspection',
                    inspectionDate: $inspectionDate,
                    weekOfYear: $week,
                    status: $status,
                    followUpRecommendations: '',
                    phoneNumber: $this->sanitizePhone($phone),
                    creationDate: ''
                );
                $this->logger->debug('Created inspection entity', ['description' => $desc, 'status' => $status]);
            } else {
                $priority = $this->determinePriority($desc);
                $status = $dt ? 'scheduled' : 'new';

                $incidents[] = new IncidentReport(
                    description: $desc,
                    type: 'incident report',
                    priority: $priority,
                    serviceVisitDate: $dt ? $dt->format('Y-m-d') : '',
                    status: $status,
                    serviceNotes: '',
                    phoneNumber: $this->sanitizePhone($phone),
                    creationDate: ''
                );
                $this->logger->debug('Created incident report entity', ['description' => $desc, 'priority' => $priority, 'status' => $status]);
            }
        }

        $this->logger->info('Finished processing', [
            'inspections' => count($inspections),
            'incident_reports' => count($incidents),
            'unprocessable' => count($unprocessable),
        ]);

        return [
            'inspections' => $inspections,
            'incidentReports' => $incidents,
            'unprocessable' => $unprocessable,
        ];
    }

    private function determinePriority(string $desc): string
    {
        $m = strtolower($desc);
        if (str_contains($m, 'very urgent')) {
            return 'critical';
        }
        if (str_contains($m, 'urgent')) {
            return 'high';
        }
        return 'normal';
    }

    private function parseDate(mixed $str): ?DateTimeImmutable
    {
        if (!$str || !is_string($str) || trim($str) === '') {
            return null;
        }
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'Y-m-d\TH:i:sP'];
        foreach ($formats as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat($fmt, $str);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }
        // try to be forgiving: if string contains only date part
        $parts = explode(' ', $str);
        if (count($parts) > 0) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $parts[0]);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }
        return null;
    }

    private function sanitizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === 'null') return '';
        return $phone;
    }
}
