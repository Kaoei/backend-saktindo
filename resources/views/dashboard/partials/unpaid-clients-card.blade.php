<!-- Card Informasi Client Belum Melakukan Pelunasan -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-0 fw-bold text-danger">
                <i class="feather icon-user-x me-1"></i> Informasi Client Belum Lunas / Belum Bayar
            </h5>
            <small class="text-muted">Daftar client dengan tagihan piutang aktif. Client dengan &ge; 3 tagihan belum lunas otomatis <strong>DIBLOKIR</strong> dari pembuatan Sales Order baru.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            @php
                $blockedCount = isset($unpaidClients) ? $unpaidClients->where('is_blocked', true)->count() : 0;
                $totalUnpaidSum = isset($unpaidClients) ? $unpaidClients->sum('total_outstanding') : 0;
            @endphp
            <span class="badge bg-danger px-3 py-2 fs-6">
                {{ $blockedCount }} Client Diblokir (&ge;3)
            </span>
            <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                Total Piutang: Rp {{ number_format($totalUnpaidSum, 0, ',', '.') }}
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>Nama Client / Customer</th>
                        <th>Kontak</th>
                        <th class="text-center">Jml Invoice Belum Lunas</th>
                        <th class="text-end">Total Sisa Tagihan</th>
                        <th class="text-center">Status Pembuatan SO</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unpaidClients ?? [] as $index => $client)
                        <tr class="{{ $client->is_blocked ? 'table-danger-subtle' : '' }}">
                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $client->customer_name }}</div>
                                @if($client->customer_id && !str_starts_with($client->customer_id, 'MANUAL-') && $client->customer_id !== 'UNKNOWN')
                                    <small class="text-muted">Kode: {{ $client->customer_id }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    <i class="feather icon-mail text-muted me-1"></i> {{ $client->email ?: '-' }}<br>
                                    <i class="feather icon-phone text-muted me-1"></i> {{ $client->phone ?: '-' }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($client->is_blocked)
                                    <span class="badge bg-danger rounded-pill px-3 py-1">
                                        <i class="feather icon-alert-octagon me-1"></i> {{ $client->unpaid_count }} Tagihan
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1">
                                        {{ $client->unpaid_count }} Tagihan
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-danger">
                                Rp {{ number_format($client->total_outstanding, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($client->is_blocked)
                                    <span class="badge bg-danger" title="Pembuatan SO ditolak oleh sistem sampai ada pelunasan">
                                        <i class="feather icon-slash me-1"></i> DIBLOKIR SO
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <i class="feather icon-check me-1"></i> Diizinkan
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-view-unpaid-invoices" 
                                        data-customer-name="{{ $client->customer_name }}"
                                        data-customer-email="{{ $client->email }}"
                                        data-unpaid-count="{{ $client->unpaid_count }}"
                                        data-total-outstanding="{{ number_format($client->total_outstanding, 0, ',', '.') }}"
                                        data-is-blocked="{{ $client->is_blocked ? '1' : '0' }}"
                                        data-invoices='@json($client->invoices)'
                                        data-bs-toggle="modal"
                                        data-bs-target="#unpaidDetailModal"
                                        data-toggle="modal"
                                        data-target="#unpaidDetailModal">
                                    <i class="feather icon-list me-1"></i> Rincian Tagihan
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="feather icon-check-circle text-success fs-4 d-block mb-1"></i>
                                Tidak ada client yang memiliki tagihan belum lunas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Rincian Tagihan Client -->
<div class="modal fade" id="unpaidDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="modalCustomerName">Rincian Tagihan Client</h5>
                    <div class="small text-muted" id="modalCustomerMeta">Informasi invoice dan riwayat piutang</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modalBlockedAlert" class="alert alert-danger m-3 mb-0" style="display: none;">
                    <i class="feather icon-alert-triangle me-1"></i>
                    <strong>Perhatian:</strong> Client ini memiliki &ge; 3 tagihan belum lunas. Pembuatan Sales Order baru untuk client ini ditolak otomatis oleh sistem sampai tagihan dilunasi.
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-bordered table-hover align-middle mb-0" id="tableClientInvoices">
                        <thead class="table-light">
                            <tr>
                                <th>No. Invoice</th>
                                <th>No. Faktur</th>
                                <th>Tanggal</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-end">Terbayar</th>
                                <th class="text-end">Sisa Tagihan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="unpaidInvoicesTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kirim Email Tagihan -->
<div class="modal fade" id="sendInvoiceEmailModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="formSendInvoiceEmail" action="">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold"><i class="feather icon-mail me-1"></i> Kirim Ulang Tagihan by Email</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Invoice</label>
                        <input type="text" class="form-control" id="emailModalInvoiceNumber" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer</label>
                        <input type="text" class="form-control" id="emailModalCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Tujuan <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" id="emailModalRecipient" placeholder="contoh: finance@perusahaan.com" required>
                        <small class="text-muted">Tagihan invoice, rincian pembayaran, dan info rekening akan dikirim ke alamat ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pesan Tambahan (Opsional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Pesan khusus untuk client (misal: Mohon konfirmasi pembayaran)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitSendEmail">
                        <i class="feather icon-send me-1"></i> Kirim Email Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Handle opening client invoices detail modal
    $(document).on('click', '.btn-view-unpaid-invoices', function() {
        const customerName = $(this).data('customer-name');
        const customerEmail = $(this).data('customer-email') || '';
        const unpaidCount = $(this).data('unpaid-count');
        const totalOutstanding = $(this).data('total-outstanding');
        const isBlocked = $(this).data('is-blocked') == '1';
        const invoices = $(this).data('invoices') || [];

        $('#modalCustomerName').text(customerName);
        $('#modalCustomerMeta').html(`Email: <strong>${customerEmail || '-'}</strong> | Total ${unpaidCount} tagihan belum lunas: <strong class="text-danger">Rp ${totalOutstanding}</strong>`);

        if (isBlocked) {
            $('#modalBlockedAlert').show();
        } else {
            $('#modalBlockedAlert').hide();
        }

        let html = '';
        if (invoices.length === 0) {
            html = '<tr><td colspan="9" class="text-center text-muted py-3">Tidak ada invoice aktif.</td></tr>';
        } else {
            invoices.forEach(function(inv) {
                const invDate = inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('id-ID') : '-';
                const dueDate = inv.due_date ? new Date(inv.due_date).toLocaleDateString('id-ID') : '-';
                const grandTotal = new Intl.NumberFormat('id-ID').format(inv.grand_total || 0);
                const paidAmount = new Intl.NumberFormat('id-ID').format(inv.paid_amount || 0);
                const outstanding = new Intl.NumberFormat('id-ID').format(inv.outstanding_amount || inv.grand_total || 0);
                const sendEmailUrl = `{{ url('finance/invoices') }}/${inv.id}/send-email`;

                let statusBadge = '<span class="badge bg-warning text-dark">Outstanding</span>';
                if (inv.status === 'partial') {
                    statusBadge = '<span class="badge bg-info text-dark">Partial</span>';
                }

                html += `
                    <tr>
                        <td class="fw-bold">${inv.invoice_number}</td>
                        <td>${inv.faktur_number || '-'}</td>
                        <td>${invDate}</td>
                        <td class="text-danger fw-semibold">${dueDate}</td>
                        <td class="text-end">Rp ${grandTotal}</td>
                        <td class="text-end text-success">Rp ${paidAmount}</td>
                        <td class="text-end fw-bold text-danger">Rp ${outstanding}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-primary btn-trigger-email"
                                    data-invoice-id="${inv.id}"
                                    data-invoice-number="${inv.invoice_number}"
                                    data-customer-name="${customerName}"
                                    data-customer-email="${customerEmail}"
                                    data-action="${sendEmailUrl}">
                                <i class="feather icon-mail me-1"></i> Kirim Email
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        $('#unpaidInvoicesTableBody').html(html);
    });

    // Handle trigger send email modal
    $(document).on('click', '.btn-trigger-email', function() {
        const invNumber = $(this).data('invoice-number');
        const custName = $(this).data('customer-name');
        const custEmail = $(this).data('customer-email') || '';
        const actionUrl = $(this).data('action');

        $('#emailModalInvoiceNumber').val(invNumber);
        $('#emailModalCustomerName').val(custName);
        $('#emailModalRecipient').val(custEmail);
        $('#formSendInvoiceEmail').attr('action', actionUrl);

        // Open Send Email Modal (supports both bootstrap 4/5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const sendModal = new bootstrap.Modal(document.getElementById('sendInvoiceEmailModal'));
            sendModal.show();
        } else {
            $('#sendInvoiceEmailModal').modal('show');
        }
    });
});
</script>
@endpush
