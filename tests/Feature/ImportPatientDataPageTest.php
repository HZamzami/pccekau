<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ImportPatientData;
use App\Models\Patient;
use App\Models\User;
use App\Support\PatientDocumentsDisk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportPatientDataPageTest extends TestCase
{
    use RefreshDatabase;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        // Uploads live on the documents disk; a fake one keeps test files out of storage/.
        $this->disk = PatientDocumentsDisk::name();
        Storage::fake($this->disk);
    }

    /**
     * Filament's FileUpload component is awkward to drive through Livewire's
     * upload-simulation testing helpers (it expects a live JS upload
     * lifecycle this doesn't go through). Instead, write the file straight
     * onto the documents disk at the path the component would have stored it
     * at, and set the form field to that already-stored relative path —
     * exactly the state analyze()/confirm() see once a real upload finishes.
     */
    /** @return array<string, string> Filament's FileUpload stores state as {uuid: path}, even for a single file. */
    private function fakeStoredHolterUpload(): array
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['MRN', null, 'Date of Holter Hookup', 'Holter Report', 'Stress ECG Report'],
            [111111, 'Test Patient One', '01-01-2025', 'Normal Holter.', null],
            [222222, 'Test Patient Two', '02-01-2025', 'Sinus tachycardia noted.', 'Normal stress ECG.'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'holter').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        $relativePath = 'imports/'.uniqid('test-holter-').'.xlsx';
        Storage::disk($this->disk)->put($relativePath, file_get_contents($tempPath));
        unlink($tempPath);

        return [(string) Str::uuid() => $relativePath];
    }

    public function test_analyzing_a_file_builds_a_rich_preview(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::actingAs($doctor)
            ->test(ImportPatientData::class)
            ->set('data.import_type', 'holter')
            ->set('data.file', $this->fakeStoredHolterUpload())
            ->call('analyze')
            ->assertSet('previewRows.0.status', 'new')
            ->assertSet('previewRows.1.status', 'new');
    }

    public function test_deselecting_a_row_excludes_it_from_the_import(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        $component = Livewire::actingAs($doctor)
            ->test(ImportPatientData::class)
            ->set('data.import_type', 'holter')
            ->set('data.file', $this->fakeStoredHolterUpload())
            ->call('analyze');

        $component->call('toggleRow', 1); // deselect the second row
        $component->callAction('import');

        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', ['mrn' => '111111']);
        $this->assertDatabaseMissing('patients', ['mrn' => '222222']);
        $this->assertDatabaseCount('ep_studies', 1);
    }

    public function test_status_filter_and_search_narrow_the_visible_rows(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        $component = Livewire::actingAs($doctor)
            ->test(ImportPatientData::class)
            ->set('data.import_type', 'holter')
            ->set('data.file', $this->fakeStoredHolterUpload())
            ->call('analyze');

        $this->assertCount(2, $component->get('previewRows'));

        $component->set('search', '111111');
        $this->assertCount(1, $component->instance()->filteredRows);
    }

    public function test_importing_creates_patients_and_ep_studies(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        $upload = $this->fakeStoredHolterUpload();

        Livewire::actingAs($doctor)
            ->test(ImportPatientData::class)
            ->set('data.import_type', 'holter')
            ->set('data.file', $upload)
            ->call('analyze')
            ->callAction('import');

        // The sheet holds patient data; it doesn't outlive the import.
        Storage::disk($this->disk)->assertMissing(reset($upload));

        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseCount('ep_studies', 3); // patient two has both a Holter and a Stress ECG record

        $patient = Patient::where('mrn', '222222')->first();
        $this->assertNotNull($patient);
        $this->assertTrue($patient->epStudies()->where('type', 'holter')->exists());
        $this->assertTrue($patient->epStudies()->where('type', 'stress_ecg')->exists());
    }

    public function test_starting_over_deletes_the_uploaded_file(): void
    {
        $upload = $this->fakeStoredHolterUpload();

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Doctor]))
            ->test(ImportPatientData::class)
            ->set('data.import_type', 'holter')
            ->set('data.file', $upload)
            ->call('analyze')
            ->call('startOver');

        Storage::disk($this->disk)->assertMissing(reset($upload));
    }
}
