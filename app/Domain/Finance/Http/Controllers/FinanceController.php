<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Models\FeeInvoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FinanceController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', FeeInvoice::class);

        return response()->json(FeeInvoice::latest()->get());
    }

    public function show(string $id): JsonResponse
    {
        $invoice = FeeInvoice::with('student')->findOrFail($id);

        Gate::authorize('view', $invoice);

        return response()->json($invoice);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'invoice_number' => ['required', 'string'],
            'title' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $invoice = FeeInvoice::create($validated);

        return response()->json($invoice, 201);
    }
}
