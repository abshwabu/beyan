<?php

namespace App\Console\Commands;

use App\Domain\Platform\Actions\ProcessSubscriptionExpirationsAction;
use Illuminate\Console\Command;

class ProcessSubscriptionExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'platform:subscriptions:check-expiry';

    /**
     * The console command description.
     */
    protected $description = 'Process subscription expirations, apply grace periods, suspend expired institutions, and queue reminder alerts';

    /**
     * Execute the console command.
     */
    public function handle(ProcessSubscriptionExpirationsAction $action): int
    {
        $this->info('Checking platform subscription statuses...');

        $results = $action->execute();

        $this->info("Completed: {$results['expired']} expired & suspended, {$results['grace_period']} entered grace period, {$results['reminders_sent']} reminders queued.");

        return self::SUCCESS;
    }
}
