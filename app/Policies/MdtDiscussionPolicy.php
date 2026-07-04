<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesClinicalRecords;

class MdtDiscussionPolicy
{
    use AuthorizesClinicalRecords;
}
