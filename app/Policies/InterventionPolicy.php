<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class InterventionPolicy
{
    use AuthorizesClinicalRecords;
}
