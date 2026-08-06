<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that all model fillables physically exist in database tables.
     */
    public function test_all_models_fillables_exist_in_database_tables(): void
    {
        $models = [
            \App\Models\User::class,
            \App\Models\Supplier::class,
            \App\Models\SupplierContact::class,
            \App\Models\SupplierProduct::class,
            \App\Models\SupplierPurchaseHistory::class,
            \App\Models\SupplierPaymentTerm::class,
            \App\Models\Product::class,
            \App\Models\Master_customer::class,
            \App\Models\SalesOrder::class,
            \App\Models\SalesOrderItem::class,
            \App\Models\Invoice::class,
            \App\Models\InvoicePayment::class,
            \App\Models\DeliveryNote::class,
            \App\Models\Rak::class,
            \App\Models\InBound::class,
            \App\Models\GudangProduct::class,
            \App\Models\WarehouseTask::class,
            \App\Models\OutBound::class,
            \App\Models\SupplierPO::class,
            \App\Models\SupplierPOItem::class,
            \App\Models\Brand::class,
            \App\Models\Category::class,
            \App\Models\SubCategory::class,
            \App\Models\Variant::class,
            \App\Models\Retur::class,
            \App\Models\InternalInvoice::class,
            \App\Models\RekeningBank::class,
            \App\Models\ProformaInvoice::class,
            \App\Models\ActivityLog::class,
        ];

        foreach ($models as $class) {
            $instance = new $class;
            $table = $instance->getTable();
            $fillable = $instance->getFillable();

            $columns = array_map('strtolower', Schema::getColumnListing($table));
            $fillable = array_map('strtolower', $instance->getFillable());
            $missingInDb = array_diff($fillable, $columns);

            $this->assertEmpty(
                $missingInDb,
                "Model [{$class}] fillable columns missing in DB table [{$table}]: " . implode(', ', $missingInDb)
            );
        }
    }
}
