<?php

namespace App\Console\Commands;

use App\Models\PatientDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePatientDocumentsToPrivate extends Command
{
    protected $signature = 'pccekau:migrate-documents {--dry-run : Report what would move without changing anything}';

    protected $description = 'Move patient document files from the public disk to the private local disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk(config('filesystems.patient_documents'));
        $dryRun = $this->option('dry-run');
        $moved = 0;
        $skipped = 0;
        $missing = 0;

        foreach (PatientDocument::withTrashed()->get() as $document) {
            foreach ($document->files ?? [] as $path) {
                if ($local->exists($path)) {
                    $skipped++;
                    continue;
                }

                if (! $public->exists($path)) {
                    $this->warn("Missing on both disks: {$path} (document #{$document->id})");
                    $missing++;
                    continue;
                }

                if (! $dryRun) {
                    $local->put($path, $public->get($path));
                    $public->delete($path);
                }

                $moved++;
            }
        }

        $prefix = $dryRun ? '[dry-run] Would move' : 'Moved';
        $this->info("{$prefix} {$moved} file(s); {$skipped} already private; {$missing} missing.");

        return self::SUCCESS;
    }
}
