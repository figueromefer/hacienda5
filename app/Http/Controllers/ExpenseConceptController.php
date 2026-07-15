<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseConceptRequest;
use App\Models\ExpenseConcept;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseConceptController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $expenseConcepts = ExpenseConcept::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('expense-concepts.index', compact('expenseConcepts', 'search'));
    }

    public function archived(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $expenseConcepts = ExpenseConcept::query()
            ->where('is_active', false)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('expense-concepts.archived', compact('expenseConcepts', 'search'));
    }

    public function create(): View
    {
        return view('expense-concepts.create');
    }

    public function store(ExpenseConceptRequest $request): RedirectResponse
    {
        ExpenseConcept::create([...$request->validated(), 'is_active' => true]);

        return redirect()->route('expense-concepts.index')
            ->with('success', 'Concepto de gasto creado correctamente.');
    }

    public function show(ExpenseConcept $expenseConcept): View
    {
        return view('expense-concepts.show', compact('expenseConcept'));
    }

    public function edit(ExpenseConcept $expenseConcept): View
    {
        return view('expense-concepts.edit', compact('expenseConcept'));
    }

    public function update(ExpenseConceptRequest $request, ExpenseConcept $expenseConcept): RedirectResponse
    {
        $expenseConcept->update($request->validated());

        return redirect()->route('expense-concepts.index')
            ->with('success', 'Concepto de gasto actualizado correctamente.');
    }

    public function destroy(ExpenseConcept $expenseConcept): RedirectResponse
    {
        $expenseConcept->update(['is_active' => false]);

        return redirect()->route('expense-concepts.index')
            ->with('success', 'Concepto de gasto archivado correctamente.');
    }

    public function restore(ExpenseConcept $expenseConcept): RedirectResponse
    {
        $expenseConcept->update(['is_active' => true]);

        return redirect()->route('expense-concepts.archived')
            ->with('success', 'Concepto de gasto restaurado correctamente.');
    }
}
