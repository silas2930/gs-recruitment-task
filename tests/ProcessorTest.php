<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\MessageProcessor;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

final class ProcessorTest extends TestCase
{
    private MessageProcessor $processor;

    protected function setUp(): void
    {
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler('php://stdout', Level::Warning));
        $this->processor = new MessageProcessor($logger);
    }

    public function testProcessingCreatesExpectedCounts(): void
    {
        $messages = [
            ['description' => 'Please schedule an inspection of the HVAC system for next week.', 'dueDate' => '2024-03-20', 'phone' => '+1-555-0123'],
            ['description' => 'AC unit not cooling properly, needs immediate attention', 'dueDate' => null, 'phone' => '555-0124'],
            ['description' => 'Very urgent! Fire alarm system malfunction detected', 'dueDate' => '2024-03-25', 'phone' => '555-0125'],
            ['description' => 'Please schedule an inspection of the HVAC system for next week.', 'dueDate' => '2024-03-20', 'phone' => '555-0126'], // duplicate
        ];

        $result = $this->processor->process($messages);

        $this->assertCount(1, $result['inspections']);
        $this->assertCount(2, $result['incidentReports']);
        $this->assertCount(1, $result['unprocessable']);

        $inspection = $result['inspections'][0];
        $this->assertSame('scheduled', $inspection->status);
        $this->assertSame('12', $inspection->weekOfYear); // 2024-03-20 is ISO week 12

        $urgentIncident = $result['incidentReports'][1];
        $this->assertSame('critical', $urgentIncident->priority);
        $this->assertSame('scheduled', $urgentIncident->status);
    }
}
