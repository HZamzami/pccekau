<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $document = $this->makeDocument();

        $this->get(route('patient-documents.download', ['document' => $document, 'index' => 0]))
            ->assertRedirect();
    }

    public function test_authenticated_viewer_can_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('patient-documents/report.pdf', 'pdf-content');

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $document = $this->makeDocument();

        $this->actingAs($viewer)
            ->get(route('patient-documents.download', ['document' => $document, 'index' => 0]))
            ->assertOk()
            ->assertDownload('report.pdf');
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('local');

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $document = $this->makeDocument();

        $this->actingAs($viewer)
            ->get(route('patient-documents.download', ['document' => $document, 'index' => 5]))
            ->assertNotFound();
    }

    private function makeDocument(): PatientDocument
    {
        return PatientDocument::create([
            'patient_id' => Patient::factory()->create()->id,
            'label' => 'Referral letter',
            'category' => 'referral',
            'files' => ['patient-documents/report.pdf'],
        ]);
    }
}
