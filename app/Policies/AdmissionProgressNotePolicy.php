<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class AdmissionProgressNotePolicy
{
    use AuthorizesClinicalRecords;
}
