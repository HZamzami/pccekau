<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class AdmissionPolicy
{
    use AuthorizesClinicalRecords;
}
