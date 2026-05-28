<div class="row justify-content-center">
    <div class="col-xl-10">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">Data Supplier</h5>
                        <small class="text-muted">{{ $supplier->exists ? $supplier->id : 'Vendor, PIC, pajak, pembayaran, dan alamat' }}</small>
                    </div>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Identitas Vendor</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control" value="{{ old('name', $supplier->name) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kode Vendor</label>
                            <input name="vendor_code" type="text" class="form-control" value="{{ old('vendor_code', $supplier->vendor_code) }}" placeholder="Opsional">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipe Vendor</label>
                            <select name="vendor_type" class="form-select">
                                @foreach(['' => 'Pilih tipe', 'Barang' => 'Barang', 'Jasa' => 'Jasa', 'Marketplace' => 'Marketplace', 'Lainnya' => 'Lainnya'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('vendor_type', $supplier->vendor_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Perusahaan</label>
                            <input name="company_name" type="text" class="form-control" value="{{ old('company_name', $supplier->company_name) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Email Perusahaan</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Telepon Perusahaan</label>
                            <input name="phone" type="text" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3">PIC Supplier</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama PIC</label>
                            <input name="pic_name" type="text" class="form-control" value="{{ old('pic_name', $supplier->pic_name) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor HP PIC</label>
                            <input name="pic_phone" type="text" class="form-control" value="{{ old('pic_phone', $supplier->pic_phone) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email PIC</label>
                            <input name="pic_email" type="email" class="form-control" value="{{ old('pic_email', $supplier->pic_email) }}">
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3">Pajak & Pembayaran</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">NPWP</label>
                            <input name="tax_number" type="text" class="form-control" value="{{ old('tax_number', $supplier->tax_number) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Termin Default</label>
                            <div class="input-group">
                                <input name="payment_due_days" type="number" min="0" class="form-control" value="{{ old('payment_due_days', $supplier->payment_due_days ?? 0) }}">
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Limit Hutang</label>
                            <input name="debt_limit" type="number" min="0" step="0.01" class="form-control" value="{{ old('debt_limit', $supplier->debt_limit ?? 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $supplier->status ?: 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bank</label>
                            <input name="bank_name" type="text" class="form-control" value="{{ old('bank_name', $supplier->bank_name) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Rekening</label>
                            <input name="bank_account_name" type="text" class="form-control" value="{{ old('bank_account_name', $supplier->bank_account_name) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nomor Rekening</label>
                            <input name="bank_account_number" type="text" class="form-control" value="{{ old('bank_account_number', $supplier->bank_account_number) }}">
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3">Alamat</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kota</label>
                            <input name="city" type="text" class="form-control" value="{{ old('city', $supplier->city) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Provinsi</label>
                            <input name="province" type="text" class="form-control" value="{{ old('province', $supplier->province) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kode Pos</label>
                            <input name="postal_code" type="text" class="form-control" value="{{ old('postal_code', $supplier->postal_code) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $supplier->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
