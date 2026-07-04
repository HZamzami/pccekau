<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class PatientPolicy
{
    use AuthorizesClinicalRecords;
}
