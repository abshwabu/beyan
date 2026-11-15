<?php

namespace App\Models;

use App\Domain\Auth\Models\User as DomainUser;

class User extends DomainUser
{
    // Inherits all domain user capabilities, relationships, and tenancy scopes
}
