<?php

namespace App\Models;

use App\Domain\Auth\Models\User as DomainUser;

class User extends DomainUser
{
    public function getMorphClass()
    {
        return DomainUser::class;
    }
}
