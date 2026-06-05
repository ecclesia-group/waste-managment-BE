<?php

namespace Database\Seeders;

use App\Services\FrontendApiDocumentationService;
use Illuminate\Database\Seeder;

/**
 * Seeds demo data and generates frontend API documentation.
 *
 * Run: php artisan db:seed --class=FrontendApiDocumentationSeeder
 */
class FrontendApiDocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeederV3::class,
            DemoDataSeeder::class,
        ]);

        $result = app(FrontendApiDocumentationService::class)->generate();

        $this->command?->info('Frontend API documentation generated.');
        $this->command?->line('  Markdown: '.$result['markdown']);
        $this->command?->line('  JSON:     '.$result['json']);
        $this->command?->line('  Endpoints documented: '.$result['endpoint_count']);
    }
}
