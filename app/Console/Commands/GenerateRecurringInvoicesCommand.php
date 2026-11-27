<?php

namespace App\Console\Commands;

use App\Domain\Finance\Actions\GenerateInvoiceAction;
use App\Domain\Institution\Models\Institution;
use Illuminate\Console\Command;

class GenerateRecurringInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'finance:generate-invoices';

    /**
     * The console command description.
     */
    protected $description = 'Automatically generate subscription tuition invoices for active students linked to fee plans';

    /**
     * Execute the console command.
     */
    public function handle(GenerateInvoiceAction $generateInvoiceAction): int
    {
        $totalGenerated = 0;

        Institution::where('status', 'active')
            ->select(['id', 'name'])
            ->chunkById(100, function ($institutions) use ($generateInvoiceAction, &$totalGenerated) {
                foreach ($institutions as $institution) {
                    $count = $generateInvoiceAction->ensureForInstitution($institution->id);
                    $totalGenerated += $count;
                }
            });

        $this->info("Generated {$totalGenerated} recurring tuition invoice(s).");

        return self::SUCCESS;
    }
}
