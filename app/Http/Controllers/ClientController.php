<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::query()->orderByDesc('id')->get();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'npwp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $clientData = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'email' => $data['email'],
        ];

        // Handle KTP upload
        if ($request->hasFile('ktp')) {
            $ktpPath = $request->file('ktp')->store('clients/ktp', 'public');
            $clientData['ktp_path'] = $ktpPath;
        }

        // Handle NPWP upload
        if ($request->hasFile('npwp')) {
            $npwpPath = $request->file('npwp')->store('clients/npwp', 'public');
            $clientData['npwp_path'] = $npwpPath;
        }

        Client::query()->create($clientData);

        return redirect()->route('clients.index')->with('status', 'Client berhasil ditambahkan.');
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email,'.$client->id],
            'ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'npwp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $client->name = $data['name'];
        $client->phone = $data['phone'];
        $client->address = $data['address'];
        $client->email = $data['email'];

        // Handle KTP upload
        if ($request->hasFile('ktp')) {
            // Delete old file if exists
            if ($client->ktp_path) {
                Storage::disk('public')->delete($client->ktp_path);
            }
            $ktpPath = $request->file('ktp')->store('clients/ktp', 'public');
            $client->ktp_path = $ktpPath;
        }

        // Handle NPWP upload
        if ($request->hasFile('npwp')) {
            // Delete old file if exists
            if ($client->npwp_path) {
                Storage::disk('public')->delete($client->npwp_path);
            }
            $npwpPath = $request->file('npwp')->store('clients/npwp', 'public');
            $client->npwp_path = $npwpPath;
        }

        $client->save();

        return redirect()->route('clients.index')->with('status', 'Client berhasil diupdate.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        // Delete uploaded files
        if ($client->ktp_path) {
            Storage::disk('public')->delete($client->ktp_path);
        }
        if ($client->npwp_path) {
            Storage::disk('public')->delete($client->npwp_path);
        }

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client berhasil dihapus.');
    }
}
