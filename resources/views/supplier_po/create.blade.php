@extends('layouts.dashboard', [
    'title' => 'Buat Purchase Order Supplier',
    'pageTitle' => 'Buat Purchase Order Supplier',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('supplier-po.index').'">Supplier PO</a></li><li class="breadcrumb-item">Buat</li>',
])

@section('content')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(isset($prefillItems) && $prefillItems->count() > 0)
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="feather icon-info me-2 f-20"></i>
            <div>
                <strong>Data dari Dashboard Kekurangan Stok</strong><br>
                <small>{{ $prefillItems->count() }} barang sudah terisi otomatis berdasarkan data kekurangan stok. Silakan pilih Supplier, review, dan simpan PO.</small>
            </div>
        </div>
    </div>
@endif

<form action="{{ route('supplier-po.store') }}" method="POST" id="po-form" class="mt-4">
    @csrf
    <div class="row">
        <!-- Main Form -->
        <div class="col-xl-9 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-semibold text-dark">Detail Barang PO</h5>
                        <small class="text-muted">Mendukung diskon bertingkat hingga 4 level dan Paket Promo Bundling</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bundleModal" data-toggle="modal" data-target="#bundleModal" id="btn-open-bundle-modal">
                            <i class="feather icon-gift me-1"></i>
                            Pilih Promo Bundling
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;" class="fw-semibold">NAMA BARANG</th>
                                    <th style="width: 120px;" class="text-center fw-semibold">QTY</th>
                                    <th style="width: 130px;" class="text-center fw-semibold">HARGA UNIT</th>
                                    <th style="min-width: 260px;" class="text-center fw-semibold">DISKON BERTINGKAT (%)</th>
                                    <th style="width: 130px;" class="text-end fw-semibold">SUBTOTAL</th>
                                    <th class="text-center" style="width: 60px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @if(isset($prefillItems) && $prefillItems->count() > 0)
                                    @foreach($prefillItems as $idx => $prefill)
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="form-select product-select" required>
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}"
                                                        {{ $prefill['product_id'] == $product->id ? 'selected' : '' }}>
                                                        {{ $product->item_name }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                                <input type="number" name="items[{{ $idx }}][qty]" class="form-control qty-input text-center px-1" min="1" value="{{ $prefill['qty'] }}" required>
                                                <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="{{ $prefill['price'] }}" required>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="number" name="items[{{ $idx }}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_1'] ?? $prefill['discount'] ?? 0 }}" placeholder="D1 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_2'] ?? 0 }}" placeholder="D2 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_3'] ?? 0 }}" placeholder="D3 %">
                                                <span class="text-muted small">+</span>
                                                <input type="number" name="items[{{ $idx }}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="{{ $prefill['discount_4'] ?? 0 }}" placeholder="D4 %">
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold text-success line-subtotal">
                                            Rp 0
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="form-select product-select" required>
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->last_purchase_price }}">
                                                    {{ $product->item_name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                            <input type="number" name="items[0][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
                                            <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="number" name="items[0][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                                            <span class="text-muted small">+</span>
                                            <input type="number" name="items[0][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success line-subtotal">
                                        Rp 0
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="add-row">
                            <i class="feather icon-plus me-1"></i> Tambah Baris Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold text-dark">Informasi PO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">No. Referensi / Surat Jalan</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Contoh: REF/2026/08/012">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Tanggal PO <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan khusus PO..."></textarea>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold text-dark">Grand Total:</span>
                        <span class="fw-bold text-success fs-5" id="grand-total">Rp 0</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="feather icon-save me-1"></i> Simpan Supplier PO
                        </button>
                        <a href="{{ route('supplier-po.index') }}" class="btn btn-light py-2">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Pilih Promo Bundling -->
<div class="modal fade" id="bundleModal" tabindex="-1" aria-labelledby="bundleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold text-dark" id="bundleModalLabel">
                    <i class="feather icon-gift text-success me-2"></i>Pilih Paket Promo Bundling
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div id="bundle-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Memuat daftar paket promo aktif...</div>
                </div>

                <div id="bundle-list-container" style="display: none;">
                    <p class="text-muted small mb-3">Pilih salah satu paket bundling di bawah dan tentukan jumlah paket yang ingin dipesan. Komponen produk beserta harga promo akan otomatis ditambahkan ke form PO.</p>
                    <div class="list-group" id="bundle-items-list">
                        <!-- Dynamic items -->
                    </div>
                </div>

                <div id="bundle-empty" class="text-center py-4 text-muted" style="display: none;">
                    <i class="feather icon-info mb-2" style="font-size: 2rem;"></i>
                    <div class="fw-semibold">Tidak ada promo bundling yang aktif saat ini.</div>
                    <small>Anda dapat membuat promo baru di menu <a href="{{ route('bundle-promos.create') }}" target="_blank">Master Promo Bundling</a>.</small>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(function() {
        let rowCount = {{ isset($prefillItems) ? $prefillItems->count() : 1 }};
        const products = @json($products);
        let activeBundles = [];

        function calculateLineSubtotal($row) {
            const qty = parseFloat($row.find('.qty-input').val()) || 0;
            const price = parseFloat($row.find('.price-input').val()) || 0;

            const d1 = parseFloat($row.find('.disc1-input').val()) || 0;
            const d2 = parseFloat($row.find('.disc2-input').val()) || 0;
            const d3 = parseFloat($row.find('.disc3-input').val()) || 0;
            const d4 = parseFloat($row.find('.disc4-input').val()) || 0;

            // Compounded 4-tier discount formula
            const netUnitPrice = price * (1 - d1 / 100) * (1 - d2 / 100) * (1 - d3 / 100) * (1 - d4 / 100);
            const lineSubtotal = netUnitPrice * qty;

            $row.find('.line-subtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(lineSubtotal)));
            return lineSubtotal;
        }

        function updateGrandTotal() {
            let total = 0;
            $('.item-row').each(function() {
                total += calculateLineSubtotal($(this));
            });
            $('#grand-total').text('Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(total)));
        }

        // Stepper buttons (+ / -)
        $(document).on('click', '.btn-qty-minus', function() {
            const $input = $(this).siblings('.qty-input');
            const currentVal = parseInt($input.val()) || 1;
            if (currentVal > 1) {
                $input.val(currentVal - 1).trigger('change');
            }
        });

        $(document).on('click', '.btn-qty-plus', function() {
            const $input = $(this).siblings('.qty-input');
            const currentVal = parseInt($input.val()) || 0;
            $input.val(currentVal + 1).trigger('change');
        });

        // Add row
        $('#add-row').on('click', function() {
            let optionsHtml = '<option value="">-- Pilih Barang --</option>';
            products.forEach(p => {
                optionsHtml += `<option value="${p.id}" data-price="${p.last_purchase_price}">${p.item_name} (${p.sku})</option>`;
            });

            const trHtml = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowCount}][product_id]" class="form-select product-select" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                            <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="1" required>
                            <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                        </div>
                    </td>
                    <td>
                        <input type="number" name="items[${rowCount}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="0" required>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <input type="number" name="items[${rowCount}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                            <span class="text-muted small">+</span>
                            <input type="number" name="items[${rowCount}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                        </div>
                    </td>
                    <td class="text-end fw-bold text-success line-subtotal">
                        Rp 0
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                            <i class="feather icon-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            const $newRow = $(trHtml);
            $('#items-body').append($newRow);
            rowCount++;
            bindRowEvents($newRow);
        });

        // Remove row
        $(document).on('click', '.remove-row', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                updateGrandTotal();
            } else {
                alert('Minimal harus ada 1 baris item PO.');
            }
        });

        function bindRowEvents($row) {
            $row.find('.product-select').on('change select2:select', function() {
                const selectedOpt = $(this).find('option:selected');
                const price = selectedOpt.attr('data-price') || 0;
                $row.find('.price-input').val(price);
                updateGrandTotal();
            });

            $row.find('.qty-input, .price-input, .disc1-input, .disc2-input, .disc3-input, .disc4-input').on('input change', function() {
                updateGrandTotal();
            });
        }

        // Promo Bundling Loader & Injector
        function loadActiveBundles() {
            $('#bundle-loading').show();
            $('#bundle-list-container').hide();
            $('#bundle-empty').hide();

            $.get('{{ route("bundle-promos.api.active") }}', function(res) {
                $('#bundle-loading').hide();
                if (res.success && res.data && res.data.length > 0) {
                    activeBundles = res.data;
                    renderBundleList();
                    $('#bundle-list-container').show();
                } else {
                    $('#bundle-empty').show();
                }
            }).fail(function() {
                $('#bundle-loading').hide();
                $('#bundle-empty').show();
            });
        }

        function renderBundleList() {
            const $list = $('#bundle-items-list');
            $list.empty();

            activeBundles.forEach((b, idx) => {
                let itemsListHtml = '';
                b.items.forEach(it => {
                    itemsListHtml += `<li class="small text-muted">${it.product_name} &bull; <strong>${it.qty} pcs</strong> (Rp ${new Intl.NumberFormat('id-ID').format(it.bundle_unit_price)}/pcs)</li>`;
                });

                const cardHtml = `
                    <div class="list-group-item list-group-item-action p-3 mb-2 border rounded">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge bg-light-primary text-primary font-monospace fw-bold me-1">${b.bundle_code}</span>
                                <strong class="fs-6 text-dark">${b.name}</strong>
                                ${b.description ? `<p class="small text-muted mb-1 mt-1">${b.description}</p>` : ''}
                                <ul class="mb-0 mt-2 ps-3">${itemsListHtml}</ul>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small"><del>Rp ${new Intl.NumberFormat('id-ID').format(b.original_price)}</del></div>
                                <h5 class="text-success fw-bold mb-1">Rp ${new Intl.NumberFormat('id-ID').format(b.bundle_price)}</h5>
                                <span class="badge bg-light-danger text-danger border border-danger-subtle mb-2">Hemat ${b.savings_percentage}%</span>
                                
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <div class="input-group input-group-sm" style="width: 110px;">
                                        <span class="input-group-text">Paket</span>
                                        <input type="number" class="form-control text-center bundle-pkg-qty" id="bundle-qty-${idx}" min="1" value="1">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-success btn-apply-bundle" data-bundle-index="${idx}">
                                        <i class="feather icon-plus me-1"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $list.append(cardHtml);
            });
        }

        $('#btn-open-bundle-modal').on('click', function() {
            loadActiveBundles();
        });

        // Apply bundle items to table
        $(document).on('click', '.btn-apply-bundle', function() {
            const bundleIdx = $(this).data('bundle-index');
            const bundle = activeBundles[bundleIdx];
            const pkgQty = parseInt($(`#bundle-qty-${bundleIdx}`).val()) || 1;

            if (!bundle || !bundle.items || bundle.items.length === 0) {
                alert('Paket promo tidak valid.');
                return;
            }

            // Check if initial row is empty (first row with 0 price)
            const $firstRow = $('#items-body .item-row:first');
            let isFirstRowEmpty = false;
            if ($('#items-body .item-row').length === 1) {
                const pVal = $firstRow.find('.product-select').val();
                const prVal = parseFloat($firstRow.find('.price-input').val()) || 0;
                if (!pVal || prVal === 0) {
                    isFirstRowEmpty = true;
                }
            }

            if (isFirstRowEmpty) {
                $('#items-body').empty();
            }

            bundle.items.forEach(it => {
                const totalItemQty = it.qty * pkgQty;
                let optionsHtml = '<option value="">-- Pilih Barang --</option>';
                products.forEach(p => {
                    const isSelected = (p.id == it.product_id) ? 'selected' : '';
                    optionsHtml += `<option value="${p.id}" data-price="${p.last_purchase_price}" ${isSelected}>${p.item_name} (${p.sku})</option>`;
                });

                const trHtml = `
                    <tr class="item-row bg-light-success-subtle">
                        <td>
                            <select name="items[${rowCount}][product_id]" class="form-select product-select" required>
                                ${optionsHtml}
                            </select>
                            <small class="text-success d-block mt-1"><i class="feather icon-gift me-1"></i>${bundle.name} (${pkgQty} paket)</small>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-qty-minus px-2">-</button>
                                <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input text-center px-1" min="1" value="${totalItemQty}" required>
                                <button type="button" class="btn btn-outline-secondary btn-qty-plus px-2">+</button>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="items[${rowCount}][price]" class="form-control price-input text-end px-1" min="0" step="0.01" value="${it.bundle_unit_price}" required>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" name="items[${rowCount}][discount_1]" class="form-control form-control-sm disc1-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D1 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_2]" class="form-control form-control-sm disc2-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D2 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_3]" class="form-control form-control-sm disc3-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D3 %">
                                <span class="text-muted small">+</span>
                                <input type="number" name="items[${rowCount}][discount_4]" class="form-control form-control-sm disc4-input text-center px-1" min="0" max="100" step="0.1" value="0" placeholder="D4 %">
                            </div>
                        </td>
                        <td class="text-end fw-bold text-success line-subtotal">
                            Rp 0
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row p-1 px-2" title="Hapus Baris">
                                <i class="feather icon-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;

                const $newRow = $(trHtml);
                $('#items-body').append($newRow);
                rowCount++;
                bindRowEvents($newRow);
            });

            updateGrandTotal();

            // Close modal
            const modalEl = document.getElementById('bundleModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                inst.hide();
            } else {
                $('#bundleModal').modal('hide');
            }
        });

        // Bind initial rows
        $('.item-row').each(function() {
            bindRowEvents($(this));
        });

        // Calculate grand total on page load
        updateGrandTotal();
    });
</script>
@endpush
