<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PatientDocumentResource\Pages\CreatePatientDocument;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
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
}
