<?php

namespace App\Http\Controllers;

use App\Models\Rak;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RakController extends Controller
{
    public function index()
    {
        $raks = Rak::all();

        return view('rak.index', compact('raks'));
    }

    public function create()
    {
        return view('rak.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'rak_kode' => 'required|string|max:255|unique:raks,rak_kode',
            'location' => 'required|string|max:255',
        ]);

        Rak::create([
            'rak_kode' => $request->rak_kode,
            'location' => $request->location,
        ]);

        return redirect()
            ->route('rak.index')
            ->with('status', 'Rak berhasil ditambahkan');
    }

    public function show(Rak $rak)
    {
        //
    }

    public function edit($rak_kode)
    {
        $rak = Rak::findOrFail($rak_kode);

        return view('rak.edit', compact('rak'));
    }

   public function update(Request $request, $rak_kode)
{
    $request->validate([
        'rak_kode' => [
            'required',
            'string',
            'max:255',
            Rule::unique('raks', 'rak_kode')->ignore($rak_kode, 'rak_kode')
        ],
        'location' => 'required|string|max:255',
    ]);

    $rak = Rak::findOrFail($rak_kode);

    $rak->update([
        'rak_kode' => $request->rak_kode,
        'location' => $request->location,
    ]);

    return redirect()
        ->route('rak.index')
        ->with('status', 'Rak berhasil diperbarui');
}
    public function destroy($rak_kode)
    {
        $rak = Rak::findOrFail($rak_kode);

        $rak->delete();

        return redirect()
            ->route('rak.index')
            ->with('status', 'Rak berhasil dihapus');
    }

}
