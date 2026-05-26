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
    public function store(Request $request)
    {
    $request->validate([
        'nama_customer' => 'required',
        'nama_pic' => 'required',
        'nomor_hp' => 'required',
        'alamat' => 'required',
        'kota' => 'required',
    ]);

    $lastCustomer = Master_customer::latest()->first();

    if ($lastCustomer) {

        $lastNumber = (int) substr($lastCustomer->id, 4);

        $newNumber = $lastNumber + 1;

    } else {

        $newNumber = 1;

    }

    $customerId = 'MCS-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

    Master_customer::create([

        'id' => $customerId,

        'nama_customer' => $request->nama_customer,
        'nama_pic' => $request->nama_pic,
        'nomor_hp' => $request->nomor_hp,
        'email' => $request->email,
        'alamat' => $request->alamat,
        'kota' => $request->kota,
        'npwp' => $request->npwp,
        'tipe_customer' => $request->tipe_customer,
        'termin' => $request->termin,
        'limit_piutang' => $request->limit_piutang,

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
            'nama_customer' => 'required',
            'nama_pic' => 'required',
            'nomor_hp' => 'required',
            'alamat' => 'required',
            'kota' => 'required',
        ]);

        $customer->update($request->all());

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

        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            $lastCustomer = Master_customer::orderBy('id', 'desc')->first();

            if ($lastCustomer) {
                $lastNumber = (int) substr($lastCustomer->id, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $customerId = 'MCS-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

            Master_customer::create([
                'id' => $customerId,
                'nama_customer' => $data['nama_customer'] ?? null,
                'nama_pic' => $data['nama_pic'] ?? null,
                'nomor_hp' => $data['nomor_hp'] ?? null,
                'email' => $data['email'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'kota' => $data['kota'] ?? null,
                'npwp' => $data['npwp'] ?? null,
                'tipe_customer' => $data['tipe_customer'] ?? 'perorangan',
                'termin' => $data['termin'] ?? 0,
                'limit_piutang' => $data['limit_piutang'] ?? 0,
            ]);
        }

        fclose($file);

        return redirect()->route('master-customer.index')->with('status', 'Data customer berhasil diimport.');
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
