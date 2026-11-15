<?php

namespace App\Domain\Institution\Events;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstitutionRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Institution $institution,
        public User $user
    ) {}
}
