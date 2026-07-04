<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class PatientDocumentPolicy
{
    use AuthorizesClinicalRecords;
}
