<?php

namespace App\Http\Controllers;

use App\Models\RekeningBank;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class RekeningBankController extends Controller
{
    public function index()
    {
        $rekenings = RekeningBank::latest()->paginate(15);
        return view('rekening-bank.index', compact('rekenings'));
    }

    public function create()
    {
        return view('rekening-bank.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'toko' => 'required|in:js,sjb',
        ]);

        RekeningBank::create([
            'id' => RekeningBank::generateId() ?? ('REK-' . now()->format('YmdHis')),
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'toko' => $request->toko,
        ]);

        return redirect()
            ->route('rekening-banks.index')
            ->with('status', 'Rekening Bank berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $rekening = RekeningBank::findOrFail($id);
        return view('rekening-bank.edit', compact('rekening'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'toko' => 'required|in:js,sjb',
        ]);

        $rekening = RekeningBank::findOrFail($id);
        $rekening->update([
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'toko' => $request->toko,
        ]);

        return redirect()
            ->route('rekening-banks.index')
            ->with('status', 'Rekening Bank berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $rekening = RekeningBank::findOrFail($id);
        $rekening->delete();
        return redirect()
            ->route('rekening-banks.index')
            ->with('status', 'Rekening Bank berhasil dihapus.');
    }
}
