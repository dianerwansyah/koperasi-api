<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use App\Models\Loan;
use Illuminate\Http\Request;


class SettlementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('limit');
        $user    = $request->user();

        $query = Settlement::with(['loan.user:id,name'])
            ->orderBy('settlement_date', 'desc');

        // Filter pencarian (nama karyawan)
        if ($user->role === 'admin' && $nama = $request->query('nama')) {
            $query->whereHas('loan.user', function ($uq) use ($nama) {
                $uq->where('name', 'like', "%{$nama}%");
            });
        }

        // Filter status settlement
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter tanggal pelunasan
        if ($startDate = $request->query('start_date')) {
            $query->whereDate('settlement_date', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->whereDate('settlement_date', '<=', $endDate);
        }

        // Format data
        $formatData = function ($settlement) {
            return [
                'id'              => $settlement->id,
                'loan_id'         => $settlement->loan_id,
                'amount'          => $settlement->amount,
                'proof_path'      => $settlement->proof_path,
                'status'          => $settlement->status,
                'settlement_date' => $settlement->settlement_date,
                'user'            => $settlement->loan->user ?? null,
            ];
        };

        if ($perPage) {
            $perPage   = (int) $perPage > 0 ? (int) $perPage : 10;
            $paginator = $query->paginate($perPage)->appends($request->query());
            $data = $paginator->getCollection()->map($formatData);

            return response()->json([
                'data'         => $data,
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ]);
        } else {
            $data = $query->get()->map($formatData);

            return response()->json([
                'data'  => $data,
                'total' => $data->count(),
            ]);
        }
    }


   public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'proof_path' => 'required|file|image|max:5120',
        ]);

        if ($request->hasFile('proof_path')) {
            $file = $request->file('proof_path');
            $path = $file->store('settlements', 'public');
            $validated['proof_path'] = $path;
        }

        $loan = Loan::findOrFail($validated['loan_id']);

        $validated['user_id'] = $request->user()->id;
        $validated['amount'] = $loan->amount;
        $validated['status'] = 'applied';
        $validated['settlement_date'] = now();

        $settlement = Settlement::create($validated);

        return response()->json($settlement, 201);
    }


    public function show(Settlement $settlement)
    {
        return $settlement;
    }

    public function update(Request $request, $id)
    {
        $settlement = Settlement::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'sometimes|in:approved,rejected',
        ]);

        $settlement->update($validated);
        
        return response()->json($settlement, 200);
    }


    public function destroy(Settlement $settlement)
    {
        $settlement->delete();
        return response()->json(null, 204);
    }
}
