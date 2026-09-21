<?php

namespace App\Console\Commands;

use App\Services\StockSyncService;
use Illuminate\Console\Command;

class SyncStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize and reconcile stock quantities between Inbound, Gudang, and Master Products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock reconciliation...');

        $report = StockSyncService::reconcileAllStock();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Master Produk Processed', $report['total_supplier_products']],
                ['Gudang Product Records Created', $report['gudang_products_created']],
                ['Catalog Products Synced', $report['catalog_products_synced']],
                ['Inbound Log Records Backfilled', $report['inbound_records_backfilled']],
                ['Total Products Synced', $report['synced_count']],
            ]
        );

        $this->info('Stock reconciliation completed successfully!');
        return Command::SUCCESS;
    }
}
