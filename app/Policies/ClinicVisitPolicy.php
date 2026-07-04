<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class ClinicVisitPolicy
{
    use AuthorizesClinicalRecords;
}
