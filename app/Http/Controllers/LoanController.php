<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['karyawan', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $perPage = $request->query('limit');

        $query = Loan::with([
            'user:id,name',
            'settlements' => function($q) {
                $q->orderBy('settlement_date', 'desc')->limit(1);
            }
        ]);

        $this->applyUserFilters($query, $user, $request);
        $this->applyDateFilters($query, $request);
        $this->applyStatusFilter($query, $request);

        if ($perPage) {
            $paginator = $query->orderBy('apply_date')
                ->paginate(max((int)$perPage, 10))
                ->appends($request->query());

            $data = $paginator->getCollection()->map(function ($loan) {
                return $this->formatLoanData($loan);
            });

            return response()->json([
                'data'         => $data,
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ]);
        }

        $data = $query->orderBy('apply_date')->get()->map(function ($loan) {
            return $this->formatLoanData($loan);
        });

        return response()->json([
            'data'  => $data,
            'total' => $data->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'apply_date' => 'required|date',
        ]);

        $user = $request->user();
        $validated['user_id'] = $user->id;
        $validated['status'] = 'applied';
        
        $loan = Loan::create($validated);

        return response()->json($loan, 201);
    }

    public function show(Loan $loan)
    {
        return $loan;
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:applied,approved,rejected',
        ]);

        $loan->update($validated);

        return response()->json($loan, 200);
    }


    public function destroy(Loan $loan)
    {
        $loan->delete();
        return response()->json(null, 204);
    }

    /* -------------------------
    |  Private Helpers
    |--------------------------*/

    private function applyUserFilters($query, $user, Request $request)
    {
        if ($user->role === 'karyawan') {
            $query->where('user_id', $user->id);
        }

        if ($user->role === 'admin' && $nama = $request->query('nama')) {
            $query->whereHas('user', function ($uq) use ($nama) {
                $uq->where('name', 'like', "%{$nama}%");
            });
        }
    }

    private function applyDateFilters($query, Request $request)
    {
        if ($startDate = $request->query('start_date')) {
            $query->whereDate('apply_date', '>=', $startDate);
        }

        if ($endDate = $request->query('end_date')) {
            $query->whereDate('apply_date', '<=', $endDate);
        }
    }

    private function applyStatusFilter($query, Request $request)
    {
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
    }

    protected function formatLoanData($loan)
    {
        $latestSettlement = $loan->settlements->first();

        return [
            'id'                => $loan->id,
            'amount'            => $loan->amount,
            'phone'             => $loan->phone,
            'address'           => $loan->address,
            'status'            => $loan->status,
            'apply_date'        => $loan->apply_date,
            'settlement_status' => $latestSettlement->status ?? null,
            'user'              => $loan->user,
        ];
    }
}
