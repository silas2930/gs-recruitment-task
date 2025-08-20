<?php
namespace App\Command;

use App\Service\MessageProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:process:messages', description: 'Process messages into inspections and incident reports.')]
final class ProcessMessagesCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'Path to the source JSON file (messages).')
             ->addOption('outdir', null, InputOption::VALUE_REQUIRED, 'Output directory', 'build')
             ->addOption('log', null, InputOption::VALUE_REQUIRED, 'Log file path', 'var/log/app.log');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string)$input->getArgument('source');
        $outdir = (string)$input->getOption('outdir');
        $logPath = (string)$input->getOption('log');

        $fs = new Filesystem();
        if (!$fs->exists($source)) {
            $output->writeln("<error>Source file not found: {$source}</error>");
            return Command::FAILURE;
        }
        $fs->mkdir($outdir);
        $fs->mkdir(dirname($logPath));

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logPath, Level::Info));

        $processor = new MessageProcessor($logger);

        $logger->info('Reading source file', ['path' => $source]);
        $json = file_get_contents($source);
        $messages = json_decode($json, true);
        
        if (!is_array($messages)) {
            $output->writeln('<error>Invalid JSON structure in source file.</error>');
            return Command::FAILURE;
        }

        $result = $processor->process($messages);

        $inspectionsPath = rtrim($outdir, '/').'/inspections.json';
        $incidentsPath = rtrim($outdir, '/').'/incident-reports.json';
        $unprocessablePath = rtrim($outdir, '/').'/unprocessable.json';

        file_put_contents($inspectionsPath, json_encode(array_map(fn($i) => $i->toArray(), $result['inspections']), JSON_PRETTY_PRINT));
        file_put_contents($incidentsPath, json_encode(array_map(fn($i) => $i->toArray(), $result['incidentReports']), JSON_PRETTY_PRINT));
        file_put_contents($unprocessablePath, json_encode($result['unprocessable'], JSON_PRETTY_PRINT));

        $summary = [
            'total_messages' => count($messages),
            'inspections_created' => count($result['inspections']),
            'incident_reports_created' => count($result['incidentReports']),
            'unprocessable_messages' => count($result['unprocessable']),
        ];

        $output->writeln('--- Summary ---');
        foreach ($summary as $k => $v) {
            $output->writeln(sprintf('%s: %s', str_replace('_', ' ', $k), $v));
        }

        $logger->info('Summary', $summary);
        $output->writeln('Output written to: '.realpath($outdir));
        $output->writeln('Log written to: '.realpath($logPath));

        return Command::SUCCESS;
    }
}
