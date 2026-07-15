<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $suppliers = Supplier::query()
            ->when($status === 'archived', fn ($query) => $query->where('is_active', false))
            ->when($status !== 'archived', fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search', 'status'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['rfc'] = $this->normalizeRfc($data['rfc'] ?? null);
        $data['is_active'] = true;

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Supplier $supplier)
    {
        $payables = $supplier->payables()->withPaidAmount()->get();
        $payableSummary = [
            'pending' => $payables->where('status', 'pending')->count(),
            'partially_paid' => $payables->where('status', 'partially_paid')->count(),
            'balance' => $payables->whereNotIn('status', ['cancelled', 'paid'])->sum->balance,
        ];

        return view('suppliers.show', compact('supplier', 'payableSummary'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validatedData($request);
        $data['rfc'] = $this->normalizeRfc($data['rfc'] ?? null);

        $supplier->update($data);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier)
    {
        if (! $supplier->is_active) {
            return redirect()->route('suppliers.index', ['status' => 'archived'])
                ->with('info', 'El proveedor ya se encontraba archivado.');
        }

        $supplier->update(['is_active' => false]);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor archivado correctamente.');
    }

    public function restore(Supplier $supplier)
    {
        if ($supplier->is_active) {
            return redirect()->route('suppliers.index')
                ->with('info', 'El proveedor ya se encontraba activo.');
        }

        $supplier->update(['is_active' => true]);

        return redirect()->route('suppliers.index', ['status' => 'archived'])
            ->with('success', 'Proveedor restaurado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function normalizeRfc(?string $rfc): ?string
    {
        if (blank($rfc)) {
            return null;
        }

        return Str::upper(preg_replace('/\s+/', '', trim($rfc)) ?? trim($rfc));
    }
}
