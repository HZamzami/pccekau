<?php

namespace Tests\Feature;

use App\Enums\ProcedureCategory;
use App\Enums\UserRole;
use App\Filament\Resources\PatientDocumentResource\Pages\CreatePatientDocument;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Support\PatientDocumentsDisk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_upload_a_document(): void
    {
        Storage::fake('local');

        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        Livewire::actingAs($doctor)
            ->test(CreatePatientDocument::class)
            ->fillForm([
                'patient_id' => $patient->id,
                'label' => 'Referral letter',
                'category' => 'surgeon_discussion_note',
                'files' => [UploadedFile::fake()->create('referral.pdf', 100, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = PatientDocument::sole();
        Storage::disk('local')->assertExists($document->files[0]);
    }

    public function test_a_misconfigured_s3_disk_falls_back_to_the_attached_bucket(): void
    {
        // Laravel Cloud: PATIENT_DOCUMENTS_DISK=s3 but no AWS_* variables, and
        // the attached bucket is configured as its own disk ("private" here).
        config([
            'filesystems.patient_documents' => 's3',
            'filesystems.disks.s3' => ['driver' => 's3', 'key' => null, 'secret' => null, 'region' => null, 'bucket' => null],
            'filesystems.disks.private' => ['driver' => 's3', 'key' => 'k', 'secret' => 's', 'region' => 'auto', 'bucket' => 'docs', 'endpoint' => 'https://example.r2.cloudflarestorage.com'],
        ]);

        $this->assertSame('private', PatientDocumentsDisk::name());

        // Also when it isn't set at all, and the bucket is the default disk.
        config(['filesystems.patient_documents' => null, 'filesystems.default' => 'private']);
        $this->assertSame('private', PatientDocumentsDisk::name());
    }

    public function test_upload_still_saves_when_the_configured_disk_is_unusable(): void
    {
        Storage::fake('local');
        config([
            'filesystems.patient_documents' => 's3',
            'filesystems.disks.s3' => ['driver' => 's3', 'key' => null, 'secret' => null, 'region' => null, 'bucket' => null],
        ]);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Doctor]))
            ->test(CreatePatientDocument::class)
            ->fillForm([
                'patient_id' => Patient::factory()->create()->id,
                'label' => 'Referral letter',
                'category' => 'referral',
                'files' => [UploadedFile::fake()->create('referral.pdf', 100, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Storage::disk('local')->assertExists(PatientDocument::sole()->files[0]);
    }

    public function test_surgeon_note_upload_from_the_waitlist_saves(): void
    {
        Storage::fake('local');
        $entry = WaitlistEntry::create(['patient_id' => Patient::factory()->create()->id, 'category' => ProcedureCategory::Surgery]);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Doctor]))
            ->test(ListWaitlistEntries::class)
            ->callTableAction('surgeonNote', $entry, [
                'label' => 'Surgeon discussion note',
                'files' => [UploadedFile::fake()->create('note.pdf', 50, 'application/pdf')],
            ])
            ->assertHasNoTableActionErrors();

        $document = PatientDocument::sole();
        $this->assertSame('surgeon_discussion_note', $document->category);
        Storage::disk('local')->assertExists($document->files[0]);
    }
}
