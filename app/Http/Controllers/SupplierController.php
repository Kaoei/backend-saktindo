<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierPaymentTerm;
use App\Models\SupplierProduct;
use App\Models\SupplierPurchaseHistory;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::query()
            ->withCount(['contacts', 'products', 'purchaseHistories', 'paymentTerms'])
            ->latest()
            ->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create', ['supplier' => new Supplier()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($this->validatedSupplier($request));
        ActivityLogger::log('create', 'supplier', $supplier);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier berhasil dibuat.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load([
            'contacts' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('name'),
            'products' => fn ($query) => $query->orderBy('item_name'),
            'purchaseHistories' => fn ($query) => $query->latest('purchase_date'),
            'paymentTerms' => fn ($query) => $query->orderByDesc('is_default')->orderBy('due_days'),
        ]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validatedSupplier($request, $supplier));
        ActivityLogger::log('update', 'supplier', $supplier);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier berhasil diupdate.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        ActivityLogger::log('delete', 'supplier', $supplier, ['name' => $supplier->name]);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Supplier berhasil dihapus.');
    }

    public function contacts()
    {
        $contacts = SupplierContact::query()
            ->with('supplier')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return view('suppliers.contacts', compact('contacts'));
    }

    public function createContact()
    {
        return view('suppliers.contacts-form', [
            'contact' => new SupplierContact(),
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.contacts.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Kontak',
        ]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $contact = SupplierContact::query()->create($this->validatedContact($request));
        ActivityLogger::log('create', 'supplier_contact', $contact);

        return $this->backToSupplier($contact->supplier_id, 'Kontak supplier berhasil dibuat.');
    }

    public function editContact(SupplierContact $contact)
    {
        return view('suppliers.contacts-form', [
            'contact' => $contact,
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.contacts.update', $contact),
            'method' => 'PUT',
            'submitLabel' => 'Update Kontak',
        ]);
    }

    public function updateContact(Request $request, SupplierContact $contact): RedirectResponse
    {
        $contact->update($this->validatedContact($request));
        ActivityLogger::log('update', 'supplier_contact', $contact);

        return $this->backToSupplier($contact->supplier_id, 'Kontak supplier berhasil diupdate.');
    }

    public function destroyContact(SupplierContact $contact): RedirectResponse
    {
        $supplierId = $contact->supplier_id;
        ActivityLogger::log('delete', 'supplier_contact', $contact, ['name' => $contact->name]);
        $contact->delete();

        return $this->backToSupplier($supplierId, 'Kontak supplier berhasil dihapus.');
    }

    public function products()
    {
        $products = SupplierProduct::query()
            ->with('supplier')
            ->orderBy('item_name')
            ->get();

        return view('suppliers.products', compact('products'));
    }

    public function createProduct()
    {
        return view('suppliers.products-form', [
            'product' => new SupplierProduct(),
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.products.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Barang Supplier',
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $product = SupplierProduct::query()->create($this->validatedProduct($request));
        ActivityLogger::log('create', 'supplier_product', $product);

        return $this->backToSupplier($product->supplier_id, 'Barang supplier berhasil dibuat.');
    }

    public function editProduct(SupplierProduct $product)
    {
        return view('suppliers.products-form', [
            'product' => $product,
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.products.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Update Barang Supplier',
        ]);
    }

    public function updateProduct(Request $request, SupplierProduct $product): RedirectResponse
    {
        $product->update($this->validatedProduct($request));
        ActivityLogger::log('update', 'supplier_product', $product);

        return $this->backToSupplier($product->supplier_id, 'Barang supplier berhasil diupdate.');
    }

    public function destroyProduct(SupplierProduct $product): RedirectResponse
    {
        $supplierId = $product->supplier_id;
        ActivityLogger::log('delete', 'supplier_product', $product, ['item_name' => $product->item_name]);
        $product->delete();

        return $this->backToSupplier($supplierId, 'Barang supplier berhasil dihapus.');
    }

    public function purchases()
    {
        $purchaseHistories = SupplierPurchaseHistory::query()
            ->with('supplier')
            ->latest('purchase_date')
            ->latest()
            ->get();

        return view('suppliers.purchases', compact('purchaseHistories'));
    }

    public function createPurchase()
    {
        return view('suppliers.purchases-form', [
            'purchaseHistory' => new SupplierPurchaseHistory(['purchase_date' => now()->toDateString()]),
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.purchases.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Riwayat',
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $purchaseHistory = SupplierPurchaseHistory::query()->create($this->validatedPurchase($request));
        ActivityLogger::log('create', 'supplier_purchase', $purchaseHistory);

        return $this->backToSupplier($purchaseHistory->supplier_id, 'Riwayat pembelian berhasil dibuat.');
    }

    public function editPurchase(SupplierPurchaseHistory $purchaseHistory)
    {
        return view('suppliers.purchases-form', [
            'purchaseHistory' => $purchaseHistory,
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.purchases.update', $purchaseHistory),
            'method' => 'PUT',
            'submitLabel' => 'Update Riwayat',
        ]);
    }

    public function updatePurchase(Request $request, SupplierPurchaseHistory $purchaseHistory): RedirectResponse
    {
        $purchaseHistory->update($this->validatedPurchase($request));
        ActivityLogger::log('update', 'supplier_purchase', $purchaseHistory);

        return $this->backToSupplier($purchaseHistory->supplier_id, 'Riwayat pembelian berhasil diupdate.');
    }

    public function destroyPurchase(SupplierPurchaseHistory $purchaseHistory): RedirectResponse
    {
        $supplierId = $purchaseHistory->supplier_id;
        ActivityLogger::log('delete', 'supplier_purchase', $purchaseHistory, ['item_name' => $purchaseHistory->item_name]);
        $purchaseHistory->delete();

        return $this->backToSupplier($supplierId, 'Riwayat pembelian berhasil dihapus.');
    }

    public function paymentTerms()
    {
        $paymentTerms = SupplierPaymentTerm::query()
            ->with('supplier')
            ->orderByDesc('is_default')
            ->orderBy('due_days')
            ->get();

        return view('suppliers.payment-terms', compact('paymentTerms'));
    }

    public function createPaymentTerm()
    {
        return view('suppliers.payment-terms-form', [
            'paymentTerm' => new SupplierPaymentTerm(),
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.payment-terms.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Termin',
        ]);
    }

    public function storePaymentTerm(Request $request): RedirectResponse
    {
        $paymentTerm = SupplierPaymentTerm::query()->create($this->validatedPaymentTerm($request));
        ActivityLogger::log('create', 'supplier_payment_term', $paymentTerm);

        return $this->backToSupplier($paymentTerm->supplier_id, 'Termin pembayaran berhasil dibuat.');
    }

    public function editPaymentTerm(SupplierPaymentTerm $paymentTerm)
    {
        return view('suppliers.payment-terms-form', [
            'paymentTerm' => $paymentTerm,
            'suppliers' => $this->supplierOptions(),
            'action' => route('suppliers.payment-terms.update', $paymentTerm),
            'method' => 'PUT',
            'submitLabel' => 'Update Termin',
        ]);
    }

    public function updatePaymentTerm(Request $request, SupplierPaymentTerm $paymentTerm): RedirectResponse
    {
        $paymentTerm->update($this->validatedPaymentTerm($request));
        ActivityLogger::log('update', 'supplier_payment_term', $paymentTerm);

        return $this->backToSupplier($paymentTerm->supplier_id, 'Termin pembayaran berhasil diupdate.');
    }

    public function destroyPaymentTerm(SupplierPaymentTerm $paymentTerm): RedirectResponse
    {
        $supplierId = $paymentTerm->supplier_id;
        ActivityLogger::log('delete', 'supplier_payment_term', $paymentTerm, ['name' => $paymentTerm->name]);
        $paymentTerm->delete();

        return $this->backToSupplier($supplierId, 'Termin pembayaran berhasil dihapus.');
    }

    private function validatedSupplier(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate($this->supplierRules($supplier), $this->validationMessages(), $this->supplierAttributes());

        $data['payment_due_days'] = $data['payment_due_days'] ?? 0;
        $data['debt_limit'] = $data['debt_limit'] ?? 0;

        return $data;
    }

    private function supplierRules(?Supplier $supplier = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'vendor_code' => ['nullable', 'string', 'max:100', Rule::unique('suppliers', 'vendor_code')->ignore($supplier?->id)],
            'vendor_type' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:50'],
            'pic_email' => ['nullable', 'email', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'payment_due_days' => ['nullable', 'integer', 'min:0'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,blocked'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'email' => 'Format :attribute tidak valid.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'unique' => ':attribute sudah digunakan.',
            'in' => ':attribute tidak valid.',
        ];
    }

    private function supplierAttributes(): array
    {
        return [
            'name' => 'nama supplier',
            'vendor_code' => 'kode vendor',
            'vendor_type' => 'tipe vendor',
            'company_name' => 'nama perusahaan',
            'pic_name' => 'nama PIC',
            'pic_phone' => 'nomor HP PIC',
            'pic_email' => 'email PIC',
            'email' => 'email perusahaan',
            'phone' => 'telepon perusahaan',
            'tax_number' => 'NPWP',
            'bank_name' => 'nama bank',
            'bank_account_name' => 'nama rekening',
            'bank_account_number' => 'nomor rekening',
            'payment_due_days' => 'termin default',
            'debt_limit' => 'limit hutang',
            'address' => 'alamat',
            'city' => 'kota',
            'province' => 'provinsi',
            'postal_code' => 'kode pos',
            'status' => 'status',
            'notes' => 'catatan',
        ];
    }

    private function validatedContact(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $data['is_primary'] = $request->boolean('is_primary');

        return $data;
    }

    private function validatedProduct(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'part_number' => ['nullable', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_order_qty' => ['nullable', 'integer', 'min:1'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['last_purchase_price'] = $data['last_purchase_price'] ?? 0;
        $data['minimum_order_qty'] = $data['minimum_order_qty'] ?? 1;
        $data['lead_time_days'] = $data['lead_time_days'] ?? 0;

        return $data;
    }

    private function validatedPurchase(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:ordered,received,returned,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_amount'] = $data['total_amount'] ?? ((float) $data['quantity'] * (float) $data['unit_price']);

        return $data;
    }

    private function validatedPaymentTerm(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'due_days' => ['required', 'integer', 'min:0'],
            'down_payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'late_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['down_payment_percent'] = $data['down_payment_percent'] ?? 0;
        $data['late_fee_percent'] = $data['late_fee_percent'] ?? 0;
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    private function supplierOptions()
    {
        return Supplier::query()->orderBy('name')->get(['id', 'name', 'vendor_code']);
    }

    private function backToSupplier(string $supplierId, string $message): RedirectResponse
    {
        return redirect()->route('suppliers.show', $supplierId)->with('status', $message);
    }
}
