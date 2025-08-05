<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Services\ExportService;
use Carbon\Carbon;

class GenerateReportCommand extends Command
{
    protected $signature = 'report:generate {type} {--start-date=} {--end-date=} {--format=csv}';
    protected $description = 'Generate various reports (sales, stock, profit)';

    protected $reportService;
    protected $exportService;

    public function __construct(ReportService $reportService, ExportService $exportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function handle()
    {
        $type = $this->argument('type');
        $startDate = $this->option('start-date') ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $this->option('end-date') ?? Carbon::now()->toDateString();
        $format = $this->option('format');

        $this->info("Generating {$type} report from {$startDate} to {$endDate}...");

        try {
            switch ($type) {
                case 'sales':
                    $result = $this->exportService->exportSalesReport($startDate, $endDate, $format);
                    break;
                case 'stock':
                    $result = $this->exportService->exportStockReport($format);
                    break;
                case 'movements':
                    $result = $this->exportService->exportStockMovements($startDate, $endDate, $format);
                    break;
                default:
                    $this->error("Unknown report type: {$type}");
                    return Command::FAILURE;
            }

            if ($result['success']) {
                $filename = storage_path('app/reports/' . $result['filename']);

                // Pastikan direktori ada
                if (!is_dir(dirname($filename))) {
                    mkdir(dirname($filename), 0755, true);
                }

                file_put_contents($filename, $result['data']);
                $this->info("Report generated successfully: {$filename}");
            } else {
                $this->error("Failed to generate report");
            }
        } catch (\Exception $e) {
            $this->error("Error generating report: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
