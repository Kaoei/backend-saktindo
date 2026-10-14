<?php

namespace App\Http\Controllers;

use App\Models\Master_customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class masterCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Master_customer::latest()->get();
        return view('master_customer.index', compact("customers"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master_customer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Generate unique Customer ID safely without collision.
     */
    public static function generateId(): string
    {
        $maxNum = Master_customer::selectRaw("MAX(CAST(SUBSTRING(id, 5) AS UNSIGNED)) as max_num")->value('max_num') ?? 0;
        $newNumber = $maxNum + 1;
        $customerId = 'MCS-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        while (Master_customer::where('id', $customerId)->exists()) {
            $newNumber++;
            $customerId = 'MCS-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        }

        return $customerId;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_customer' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (Master_customer::whereRaw('LOWER(nama_customer) = ?', [strtolower(trim($value))])->exists()) {
                        $fail('Customer dengan nama "' . $value . '" sudah terdaftar.');
                    }
                },
            ],
            'nama_pic' => 'required|string|max:255',
            'nomor_hp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:50',
            'tipe_customer' => 'nullable|in:perorangan,perusahaan',
            'termin' => 'nullable|integer|min:0',
            'limit_piutang' => 'nullable|numeric|min:0',
        ], [
            'nama_customer.required' => 'Nama customer wajib diisi.',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
        ]);

        $customerId = self::generateId();

        Master_customer::create([
            'id' => $customerId,
            'nama_customer' => trim($request->nama_customer),
            'nama_pic' => trim($request->nama_pic),
            'nomor_hp' => $request->filled('nomor_hp') ? trim($request->nomor_hp) : null,
            'email' => $request->filled('email') ? trim($request->email) : null,
            'alamat' => $request->filled('alamat') ? trim($request->alamat) : null,
            'kota' => $request->filled('kota') ? trim($request->kota) : null,
            'npwp' => $request->filled('npwp') ? trim($request->npwp) : null,
            'tipe_customer' => $request->tipe_customer ?: 'perusahaan',
            'termin' => (int) ($request->termin ?? 0),
            'limit_piutang' => (float) ($request->limit_piutang ?? 0),
        ]);

        return redirect()
            ->route('master-customer.index')
            ->with('status', 'Customer berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Master_customer $master_customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Master_customer::findOrFail($id);

        return view('master_customer.edit', compact('customer'));
    }

    public function update(Request $request, string $id)
    {
        $customer = Master_customer::findOrFail($id);

        $request->validate([
            'nama_customer' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($id) {
                    if (Master_customer::where('id', '!=', $id)->whereRaw('LOWER(nama_customer) = ?', [strtolower(trim($value))])->exists()) {
                        $fail('Customer dengan nama "' . $value . '" sudah terdaftar.');
                    }
                },
            ],
            'nama_pic' => 'required|string|max:255',
            'nomor_hp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:50',
            'tipe_customer' => 'nullable|in:perorangan,perusahaan',
            'termin' => 'nullable|integer|min:0',
            'limit_piutang' => 'nullable|numeric|min:0',
        ]);

        $customer->update([
            'nama_customer' => trim($request->nama_customer),
            'nama_pic' => trim($request->nama_pic),
            'nomor_hp' => $request->filled('nomor_hp') ? trim($request->nomor_hp) : null,
            'email' => $request->filled('email') ? trim($request->email) : null,
            'alamat' => $request->filled('alamat') ? trim($request->alamat) : null,
            'kota' => $request->filled('kota') ? trim($request->kota) : null,
            'npwp' => $request->filled('npwp') ? trim($request->npwp) : null,
            'tipe_customer' => $request->tipe_customer ?: $customer->tipe_customer,
            'termin' => (int) ($request->termin ?? 0),
            'limit_piutang' => (float) ($request->limit_piutang ?? 0),
        ]);

        return redirect()
            ->route('master-customer.index')
            ->with('status', 'Customer berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Master_customer::findOrFail($id);

        $customer->delete();

        return redirect()
            ->route('master-customer.index')
            ->with('status', 'Customer berhasil dihapus');
    }   

    public function importPage()
    {
        return view('master_customer.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = fopen($request->file('file')->getRealPath(), 'r');

        $rawHeader = fgetcsv($file);
        if (!$rawHeader) {
            fclose($file);
            return redirect()->route('master-customer.index')->with('status', 'File CSV kosong.');
        }

        // Clean UTF-8 BOM and trim header keys
        $header = array_map(function ($h) {
            $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h);
            return trim(strtolower($cleaned));
        }, $rawHeader);

        $createdCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }

            // Pad row if it has fewer elements than header
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            } elseif (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $data = array_combine($header, $row);

            $namaCustomer = trim($data['nama_customer'] ?? '');
            if ($namaCustomer === '') {
                continue;
            }

            $nomorHp = trim($data['nomor_hp'] ?? '');
            $email = trim($data['email'] ?? '');

            // Deduplication check: match by nama_customer, or by nomor_hp / email
            $existing = Master_customer::whereRaw('LOWER(nama_customer) = ?', [strtolower($namaCustomer)])->first();
            if (!$existing && !empty($nomorHp)) {
                $existing = Master_customer::where('nomor_hp', $nomorHp)->first();
            }
            if (!$existing && !empty($email)) {
                $existing = Master_customer::where('email', $email)->first();
            }

            if ($existing) {
                // Update existing record to prevent duplicates
                $existing->update([
                    'nama_pic' => !empty($data['nama_pic']) ? trim($data['nama_pic']) : $existing->nama_pic,
                    'nomor_hp' => !empty($nomorHp) ? $nomorHp : $existing->nomor_hp,
                    'email' => !empty($email) ? $email : $existing->email,
                    'alamat' => isset($data['alamat']) && trim($data['alamat']) !== '' ? trim($data['alamat']) : $existing->alamat,
                    'kota' => isset($data['kota']) && trim($data['kota']) !== '' ? trim($data['kota']) : $existing->kota,
                    'npwp' => !empty($data['npwp']) ? trim($data['npwp']) : $existing->npwp,
                    'tipe_customer' => !empty($data['tipe_customer']) && in_array(strtolower(trim($data['tipe_customer'])), ['perorangan', 'perusahaan'])
                        ? strtolower(trim($data['tipe_customer']))
                        : $existing->tipe_customer,
                    'termin' => isset($data['termin']) && is_numeric($data['termin']) ? (int) $data['termin'] : $existing->termin,
                    'limit_piutang' => isset($data['limit_piutang']) && is_numeric($data['limit_piutang']) ? (float) $data['limit_piutang'] : $existing->limit_piutang,
                ]);
                $updatedCount++;
            } else {
                $customerId = self::generateId();

                Master_customer::create([
                    'id' => $customerId,
                    'nama_customer' => $namaCustomer,
                    'nama_pic' => trim($data['nama_pic'] ?? '') ?: '-',
                    'nomor_hp' => $nomorHp ?: null,
                    'email' => $email ?: null,
                    'alamat' => trim($data['alamat'] ?? '') ?: null,
                    'kota' => trim($data['kota'] ?? '') ?: null,
                    'npwp' => trim($data['npwp'] ?? '') ?: null,
                    'tipe_customer' => !empty($data['tipe_customer']) && in_array(strtolower(trim($data['tipe_customer'])), ['perorangan', 'perusahaan'])
                        ? strtolower(trim($data['tipe_customer']))
                        : 'perusahaan',
                    'termin' => isset($data['termin']) && is_numeric($data['termin']) ? (int) $data['termin'] : 0,
                    'limit_piutang' => isset($data['limit_piutang']) && is_numeric($data['limit_piutang']) ? (float) $data['limit_piutang'] : 0,
                ]);
                $createdCount++;
            }
        }

        fclose($file);

        $msg = "Data customer berhasil diproses: {$createdCount} customer baru ditambahkan";
        if ($updatedCount > 0) {
            $msg .= ", {$updatedCount} data duplikat diperbarui tanpa membuat entri ganda";
        }
        $msg .= ".";

        return redirect()->route('master-customer.index')->with('status', $msg);
    }
    public function tamplate()
    {
        $filename = 'template_customer.csv';

        $headers = [
            'nama_customer',
            'nama_pic',
            'nomor_hp',
            'email',
            'alamat',
            'kota',
            'npwp',
            'tipe_customer',
            'termin',
            'limit_piutang',
        ];

        $sample = [
            'PT Contoh Sukses',
            'Budi Santoso',
            '081234567890',
            'contoh@gmail.com',
            'Jl. Merdeka No. 1',
            'Jakarta',
            '123456789012345',
            'perusahaan',
            '30',
            '5000000',
        ];

        $callback = function () use ($headers, $sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, $sample);
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
    public function export()
    {
        $filename = 'master_customer_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $customers = Master_customer::all();

        $callback = function () use ($customers) {

            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'id',
                'nama_customer',
                'nama_pic',
                'nomor_hp',
                'email',
                'alamat',
                'kota',
                'npwp',
                'tipe_customer',
                'termin',
                'limit_piutang',
            ]);

            foreach ($customers as $customer) {

                fputcsv($file, [
                    $customer->id,
                    $customer->nama_customer,
                    $customer->nama_pic,
                    $customer->nomor_hp,
                    $customer->email,
                    $customer->alamat,
                    $customer->kota,
                    $customer->npwp,
                    $customer->tipe_customer,
                    $customer->termin,
                    $customer->limit_piutang,
                ]);

            }

            fclose($file);

        };

        return response()->stream($callback, 200, $headers);
    }
}
