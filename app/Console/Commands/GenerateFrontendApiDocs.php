<?php

namespace App\Console\Commands;

use App\Services\FrontendApiDocumentationService;
use Illuminate\Console\Command;

class GenerateFrontendApiDocs extends Command
{
    protected $signature = 'docs:frontend-api {--output= : Output directory (default: docs/frontend-api)}';

    protected $description = 'Generate frontend API reference (payloads, sample responses, demo credentials)';

    public function handle(FrontendApiDocumentationService $service): int
    {
        $output = $this->option('output');

        $result = $service->generate($output ?: null);

        $this->info('Frontend API documentation generated.');
        $this->line('  Markdown: '.$result['markdown']);
        $this->line('  JSON:     '.$result['json']);
        $this->line('  Endpoints: '.$result['endpoint_count']);

        return self::SUCCESS;
    }
}
