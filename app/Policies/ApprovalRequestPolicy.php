<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class ApprovalRequestPolicy
{
    use AuthorizesClinicalRecords;
}
