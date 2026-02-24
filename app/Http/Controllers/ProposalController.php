<?php

namespace App\Http\Controllers;

use App\Mail\ProposalMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProposalController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $proposals = Proposal::query()
            ->with(['client', 'creator', 'invoice'])
            ->when(
                $user->role === User::ROLE_SALES,
                fn ($q) => $q->where('created_by', $user->id)
            )
            ->orderByDesc('id')
            ->get();

        return view('proposals.index', compact('proposals'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();

        return view('proposals.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id'   => ['required', 'exists:clients,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string'],
        ]);

        Proposal::query()->create([
            'client_id'          => $data['client_id'],
            'created_by'         => auth()->id(),
            'title'              => $data['title'],
            'description'        => $data['description'],
            'amount'             => $data['amount'],
            'status'             => Proposal::STATUS_PENDING,
            'follow_up_deadline' => now()->addDays(14)->toDateString(),
            'notes'              => $data['notes'] ?? null,
        ]);

        return redirect()->route('proposals.index')->with('status', 'Penawaran berhasil dibuat.');
    }

    public function show(Proposal $proposal)
    {
        $proposal->load(['client', 'creator', 'invoice.creator']);

        return view('proposals.show', compact('proposal'));
    }

    public function edit(Proposal $proposal)
    {
        $clients = Client::orderBy('name')->get();

        return view('proposals.edit', compact('proposal', 'clients'));
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate([
            'client_id'   => ['required', 'exists:clients,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string'],
        ]);

        $proposal->update($data);

        return redirect()->route('proposals.show', $proposal)->with('status', 'Penawaran berhasil diupdate.');
    }

    public function updateStatus(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Proposal::STATUSES)],
            'notes'  => ['nullable', 'string'],
        ]);

        $oldStatus = $proposal->status;
        $proposal->status = $data['status'];

        if (! empty($data['notes'])) {
            $proposal->notes = $data['notes'];
        }

        $proposal->save();

        // Auto-create invoice task when approved
        if ($data['status'] === Proposal::STATUS_APPROVED && $oldStatus !== Proposal::STATUS_APPROVED) {
            if (! $proposal->invoice()->exists()) {
                // Find a finance user to assign, or fall back to current user
                $financeUser = User::where('role', User::ROLE_FINANCE)->first();

                Invoice::query()->create([
                    'proposal_id' => $proposal->id,
                    'amount'      => $proposal->amount,
                    'status'      => Invoice::STATUS_PENDING,
                    'created_by'  => $financeUser?->id ?? auth()->id(),
                ]);
            }
        }

        return redirect()->route('proposals.show', $proposal)->with('status', 'Status penawaran berhasil diupdate.');
    }

    public function sendEmail(Proposal $proposal): RedirectResponse
    {
        try {
            Mail::to($proposal->client->email)->send(new ProposalMail($proposal));
            $proposal->email_sent_at = now();
            $proposal->save();

            return redirect()->route('proposals.show', $proposal)->with('status', 'Email penawaran berhasil dikirim ke ' . $proposal->client->email . '.');
        } catch (\Exception $e) {
            return redirect()->route('proposals.show', $proposal)->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $proposal->delete();

        return redirect()->route('proposals.index')->with('status', 'Penawaran berhasil dihapus.');
    }
}
