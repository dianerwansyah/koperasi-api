<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use Illuminate\Http\Request;

class SavingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('limit');
        $user    = $request->user();

        // Base query
        $baseQuery = Saving::with('user:id,name');
        $this->applyUserFilters($baseQuery, $user, $request);

        // Summary (tidak ikut filter tanggal/type)
        $summary = $this->calculateSummary(clone $baseQuery);

        // Query utama (ikut semua filter)
        $query = clone $baseQuery;
        $this->applyDateFilters($query, $request);
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Return paginated
        if ($perPage) {
            $paginator = $query->orderBy('date')
                ->paginate(max((int)$perPage, 10))
                ->appends($request->query());

            $data = $paginator->getCollection()->map(function ($saving) {
                return $this->formatSavingData($saving);
            });

            return response()->json([
                'data'          => $data,
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'summary'       => $summary,
            ]);
        }

        $data = $query->orderBy('date')->get()->map(function ($saving) {
            return $this->formatSavingData($saving);
        });
        return response()->json([
            'data'    => $data,
            'total'   => $data->count(),
            'summary' => $summary,
        ]);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date',
            'type'    => 'required|in:wajib,pokok',
            'value'   => 'nullable|numeric',
            'id'      => 'nullable|exists:savings,id',
        ]);

        $userId   = $request->user_id;
        $date     = $request->date;
        $type     = $request->type;
        $value    = isset($request->value) ? (float)$request->value : 0;
        $savingId = $request->id ?? null;

        $year = date('Y', strtotime($date));

        // Total akumulasi simpanan wajib tahun ini
        $akumulasiSimpananWajib = Saving::where('user_id', $userId)
            ->where('type', 'wajib')
            ->whereYear('date', $year)
            ->sum('value');

        if ($type === 'wajib') {
            if ($savingId) {
                // Update: kurangi nilai lama, tambahkan nilai baru
                $existingSaving = Saving::find($savingId);
                if ($existingSaving) {
                    $akumulasiSimpananWajib = $akumulasiSimpananWajib - $existingSaving->value + $value;
                }
            } else {
                // Add baru
                $akumulasiSimpananWajib += $value;
            }
        }

        $bagiHasilTahunan = (($akumulasiSimpananWajib * 0.93) * 0.1 / 12) * 0.6;
        $bagiHasilTahunan = round($bagiHasilTahunan, 2);

        return response()->json([
            'akumulasi_simpanan_wajib' => $akumulasiSimpananWajib,
            'bagi_hasil_tahunan'       => $bagiHasilTahunan,
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'value' => 'required|numeric|min:0',
            'type' => 'required|in:wajib,pokok',
            'date' => 'required|date',
        ]);

        $saving = Saving::create($validated);
        return response()->json($saving, 201);
    }

    public function show(Saving $saving)
    {
        return $saving;
    }

    public function update(Request $request, $id)
    {
        $saving = Saving::find($id);
        if (!$saving) {
            return response()->json(['error' => 'Data simpanan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'id'      => 'required|exists:savings,id',
            'user_id' => 'required|exists:users,id',
            'type'    => 'required|in:wajib,pokok',
            'date'    => 'required|date',
            'value'   => 'required|numeric|min:0',
        ]);

        if ((int)$validated['id'] !== (int)$id) {
            return response()->json(['error' => 'ID tidak sesuai'], 422);
        }

        $saving->update([
            'user_id' => $validated['user_id'],
            'type'    => $validated['type'],
            'date'    => $validated['date'],
            'value'   => $validated['value'],
        ]);

        if ($validated['type'] === 'wajib') {
            $calculateData = [
                'user_id' => $validated['user_id'],
                'date'    => $validated['date'],
                'type'    => $validated['type'],
                'value'   => $validated['value'],
                'id'      => $saving->id,
            ];

            $bagiHasil = $this->calculate(new Request($calculateData));
            $saving->bagi_hasil = round((float)$bagiHasil->original['bagi_hasil_tahunan'], 2);
            $saving->save();
        }

        return response()->json($saving, 200);
    }


    public function destroy($id)
    {
        $saving = Saving::findOrFail($id);
        $saving->delete();
        return response()->json(['message' => 'Deleted'], 200);
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
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate = $request->query('end_date')) {
            $query->whereDate('date', '<=', $endDate);
        }
    }

    private function calculateSummary($query)
    {
        $allData = $query->get();

        $totalWajib = $allData->where('type', 'wajib')->sum('value');
        $totalPokok = $allData->where('type', 'pokok')->sum('value');

        $totalBagiHasil = $allData->sum(function ($saving) {
            $month = date('m', strtotime($saving->date));
            $year  = date('Y', strtotime($saving->date));

            $akumulasi = Saving::where('user_id', $saving->user_id)
                ->where('type', 'wajib')
                ->whereYear('date', $year)
                ->whereMonth('date', '<=', $month)
                ->sum('value');

            return (($akumulasi * 0.93) * 0.1 / 12) * 0.6;
        });

        return [
            'total_wajib'      => $totalWajib,
            'total_pokok'      => $totalPokok,
            'total_bagi_hasil' => $totalBagiHasil,
        ];
    }

    private function formatSavingData($saving)
    {
        $month = date('m', strtotime($saving->date));
        $year  = date('Y', strtotime($saving->date));

        $akumulasi = Saving::where('user_id', $saving->user_id)
            ->where('type', 'wajib')
            ->whereYear('date', $year)
            ->whereMonth('date', '<=', $month)
            ->sum('value');

        $bagiHasil = (($akumulasi * 0.93) * 0.1 / 12) * 0.6;

        return [
            'id'         => $saving->id,
            'date'       => $saving->date,
            'type'       => $saving->type,
            'value'      => $saving->value,
            'bagi_hasil' => $bagiHasil,
            'user'       => $saving->user,
        ];
    }
}
