<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class StaffPolicy
{
    use AuthorizesClinicalRecords;
}
