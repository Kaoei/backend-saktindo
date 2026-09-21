<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Product;
use App\Models\Rak;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockSyncService
{
    /**
     * Synchronize a SupplierProduct with the TikTok Product catalog model.
     * Ensures quantity, name, price, brand, and category are identical.
     */
    public static function syncProductCatalog(SupplierProduct $supplierProduct): ?Product
    {
        try {
            $totalQty = (int) $supplierProduct->gudangProducts()->sum('qty');
            $price = (float) $supplierProduct->last_purchase_price;
            if ($price <= 0) {
                $price = (float) ($supplierProduct->gudangProducts()->max('price') ?? 0);
            }

            // Find matching TikTok Product by seller_sku or product_name
            $product = null;
            if (!empty($supplierProduct->sku)) {
                $product = Product::where('seller_sku', $supplierProduct->sku)->first();
            }

            if (!$product && !empty($supplierProduct->item_name)) {
                $product = Product::where('product_name', $supplierProduct->item_name)->first();
            }

            $productData = [
                'seller_sku' => $supplierProduct->sku ?: ('SKU-' . strtoupper(substr(md5($supplierProduct->item_name), 0, 8))),
                'product_name' => $supplierProduct->item_name,
                'brand' => $supplierProduct->brand,
                'category' => $supplierProduct->category,
                'sub_category' => $supplierProduct->sub_category,
                'price' => $price,
                'quantity' => $totalQty,
            ];

            if ($product) {
                $product->update($productData);
            } else {
                $product = Product::create($productData);
            }

            return $product;
        } catch (\Exception $e) {
            Log::error('StockSyncService::syncProductCatalog error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * When stock is added or updated directly in Gudang (manual / import),
     * this method ensures:
     * 1. SupplierProduct exists and has updated metadata.
     * 2. An InBound audit log record exists (marked as 'stored') if requested.
     * 3. Catalog (Product model) total quantity matches GudangProduct total.
     */
    public static function syncGudangStock(
        GudangProduct $gudangProduct,
        bool $createInboundRecord = false,
        ?string $supplierId = null,
        ?string $notes = 'Pencatatan Stok Gudang'
    ): void {
        $supplierProduct = $gudangProduct->supplierProduct;
        if (!$supplierProduct && $gudangProduct->supplier_product_id) {
            $supplierProduct = SupplierProduct::find($gudangProduct->supplier_product_id);
        }

        if ($supplierProduct) {
            // Synchronize price if supplierProduct has 0
            if ((float) $supplierProduct->last_purchase_price <= 0 && (float) $gudangProduct->price > 0) {
                $supplierProduct->update(['last_purchase_price' => $gudangProduct->price]);
            }

            // Create inbound receipt log if requested
            if ($createInboundRecord && $gudangProduct->qty > 0) {
                $supplier = null;
                if ($supplierId) {
                    $supplier = Supplier::find($supplierId);
                }
                if (!$supplier) {
                    $supplier = $supplierProduct->supplier ?: Supplier::first();
                }

                if (!$supplier) {
                    $supplier = Supplier::create([
                        'name' => 'Supplier General',
                        'status' => 'active',
                    ]);
                }

                InBound::create([
                    'id' => InBound::generateId(),
                    'supplier_id' => $supplier->id,
                    'supplier_product_id' => $supplierProduct->id,
                    'qty_received' => (int) $gudangProduct->qty,
                    'qty_damaged' => 0,
                    'qty_missing' => 0,
                    'hpp' => $gudangProduct->price ?: 0,
                    'received_date' => now()->toDateString(),
                    'status' => 'stored',
                    'notes' => $notes ?: 'Pencatatan Stok Gudang (Auto Sync)',
                ]);
            }

            // Sync with Product catalog
            self::syncProductCatalog($supplierProduct);
        }
    }

    /**
     * Store an InBound record directly to a warehouse rack.
     * Creates or updates GudangProduct, marks InBound as stored, and syncs Product catalog.
     */
    public static function storeInboundToRack(
        InBound $inbound,
        string $rackId,
        ?string $gudangType = null,
        ?int $qtyToStore = null
    ): GudangProduct {
        $inbound->loadMissing('supplierProduct');
        $supplierProduct = $inbound->supplierProduct;

        $qty = $qtyToStore !== null ? $qtyToStore : (int) $inbound->qty_received;
        if ($qty <= 0) {
            $qty = 0;
        }

        // Determine gudang type
        if (!$gudangType) {
            $rack = Rak::where('rak_kode', $rackId)->first();
            $gudangType = strtoupper($rack->gudang ?? 'JS');
        }

        $existingProduct = GudangProduct::where('supplier_product_id', $inbound->supplier_product_id)
            ->where('rack_id', $rackId)
            ->first();

        if ($existingProduct) {
            $existingProduct->update([
                'qty' => $existingProduct->qty + $qty,
                'gudang_type' => $gudangType,
                'price' => (float) $inbound->hpp > 0 ? (float) $inbound->hpp : $existingProduct->price,
                'status' => 'stored',
            ]);
            $gudangProduct = $existingProduct;
        } else {
            $sku = $supplierProduct->sku ?? null;
            $gudangProduct = GudangProduct::create([
                'id' => GudangProduct::generateId($sku),
                'supplier_product_id' => $inbound->supplier_product_id,
                'rack_id' => $rackId,
                'gudang_type' => $gudangType,
                'qty' => $qty,
                'price' => $inbound->hpp ?: 0,
                'discount' => 0,
                'status' => 'stored',
            ]);
        }

        $inbound->update([
            'status' => 'stored',
        ]);

        if ($supplierProduct) {
            self::syncProductCatalog($supplierProduct);
        }

        return $gudangProduct;
    }

    /**
     * Adjust GudangProduct stock when an InBound record's quantity is changed.
     */
    public static function handleInboundQtyUpdate(InBound $inbound, int $oldQty, int $newQty): void
    {
        if ($inbound->status !== 'stored') {
            return;
        }

        $diff = $newQty - $oldQty;
        if ($diff === 0) {
            return;
        }

        // Find primary or latest GudangProduct for this supplier_product
        $gudangProduct = GudangProduct::where('supplier_product_id', $inbound->supplier_product_id)->first();
        if ($gudangProduct) {
            $updatedQty = max(0, $gudangProduct->qty + $diff);
            $gudangProduct->update(['qty' => $updatedQty]);
        }

        if ($inbound->supplierProduct) {
            self::syncProductCatalog($inbound->supplierProduct);
        }
    }

    /**
     * Adjust GudangProduct stock when an InBound is cancelled or deleted.
     */
    public static function handleInboundCancelOrDelete(InBound $inbound): void
    {
        if ($inbound->status !== 'stored') {
            return;
        }

        $qtyToRemove = (int) $inbound->qty_received;
        if ($qtyToRemove <= 0) {
            return;
        }

        // Deduct from GudangProduct(s)
        $gudangProducts = GudangProduct::where('supplier_product_id', $inbound->supplier_product_id)
            ->orderBy('qty', 'desc')
            ->get();

        $remainingToRemove = $qtyToRemove;
        foreach ($gudangProducts as $gp) {
            if ($remainingToRemove <= 0) {
                break;
            }
            if ($gp->qty >= $remainingToRemove) {
                $gp->update(['qty' => $gp->qty - $remainingToRemove]);
                $remainingToRemove = 0;
            } else {
                $remainingToRemove -= $gp->qty;
                $gp->update(['qty' => 0]);
            }
        }

        if ($inbound->supplierProduct) {
            self::syncProductCatalog($inbound->supplierProduct);
        }
    }

    /**
     * Reconcile all stock across:
     * - SupplierProduct (Master Produk)
     * - GudangProduct (Stok Gudang)
     * - InBound (Barang Masuk)
     * - Product (TikTok Catalog)
     * 
     * Returns a summary report array.
     */
    public static function reconcileAllStock(): array
    {
        $report = [
            'total_supplier_products' => 0,
            'gudang_products_created' => 0,
            'catalog_products_synced' => 0,
            'inbound_records_backfilled' => 0,
            'synced_count' => 0,
        ];

        DB::transaction(function () use (&$report) {
            // Default rack & supplier for orphans
            $defaultRack = Rak::first();
            if (!$defaultRack) {
                $defaultRack = Rak::create([
                    'rak_kode' => 'RAK-01',
                    'location' => 'Gudang Utama',
                    'gudang' => 'js',
                ]);
            }

            $defaultSupplier = Supplier::first();
            if (!$defaultSupplier) {
                $defaultSupplier = Supplier::create([
                    'name' => 'Supplier General',
                    'status' => 'active',
                ]);
            }

            // 1. Sync all Product (TikTok table) records into SupplierProduct if missing
            $catalogProducts = Product::all();
            foreach ($catalogProducts as $cp) {
                $sku = $cp->seller_sku ?: ($cp->sku_id ?: 'SKU-' . strtoupper(substr(md5($cp->product_name), 0, 8)));
                $sp = SupplierProduct::where('sku', $sku)
                    ->orWhere('item_name', $cp->product_name)
                    ->first();

                if (!$sp) {
                    $sp = SupplierProduct::create([
                        'supplier_id' => $defaultSupplier->id,
                        'sku' => $sku,
                        'item_name' => $cp->product_name,
                        'brand' => $cp->brand,
                        'category' => $cp->category,
                        'sub_category' => $cp->sub_category,
                        'last_purchase_price' => $cp->price ?: 0,
                        'unit' => 'pcs',
                        'status' => 'active',
                    ]);

                    // If catalog product had quantity > 0, create GudangProduct
                    if ($cp->quantity > 0) {
                        GudangProduct::create([
                            'id' => GudangProduct::generateId($sku),
                            'supplier_product_id' => $sp->id,
                            'rack_id' => $defaultRack->rak_kode,
                            'gudang_type' => strtoupper($defaultRack->gudang ?? 'JS'),
                            'qty' => (int) $cp->quantity,
                            'price' => $cp->price ?: 0,
                            'discount' => 0,
                            'status' => 'stored',
                        ]);
                        $report['gudang_products_created']++;
                    }
                }
            }

            // 2. Iterate through all SupplierProducts and ensure GudangProduct & Catalog sync
            $allSupplierProducts = SupplierProduct::with('gudangProducts')->get();
            $report['total_supplier_products'] = $allSupplierProducts->count();

            foreach ($allSupplierProducts as $sp) {
                // Ensure at least one GudangProduct entry exists (or create one with 0 qty if empty)
                if ($sp->gudangProducts->isEmpty()) {
                    GudangProduct::create([
                        'id' => GudangProduct::generateId($sp->sku),
                        'supplier_product_id' => $sp->id,
                        'rack_id' => $defaultRack->rak_kode,
                        'gudang_type' => strtoupper($defaultRack->gudang ?? 'JS'),
                        'qty' => 0,
                        'price' => $sp->last_purchase_price ?: 0,
                        'discount' => 0,
                        'status' => 'stored',
                    ]);
                    $report['gudang_products_created']++;
                }

                // Check if InBound history exists for products with physical stock
                $currentStock = (int) $sp->gudangProducts->sum('qty');
                $hasInbound = InBound::where('supplier_product_id', $sp->id)->exists();

                if ($currentStock > 0 && !$hasInbound) {
                    InBound::create([
                        'id' => InBound::generateId(),
                        'supplier_id' => $sp->supplier_id ?: $defaultSupplier->id,
                        'supplier_product_id' => $sp->id,
                        'qty_received' => $currentStock,
                        'qty_damaged' => 0,
                        'qty_missing' => 0,
                        'hpp' => $sp->last_purchase_price ?: 0,
                        'received_date' => now()->toDateString(),
                        'status' => 'stored',
                        'notes' => 'Saldo Awal Stok (Auto Reconcile)',
                    ]);
                    $report['inbound_records_backfilled']++;
                }

                // Sync catalog
                self::syncProductCatalog($sp);
                $report['catalog_products_synced']++;
                $report['synced_count']++;
            }
        });

        return $report;
    }
}
