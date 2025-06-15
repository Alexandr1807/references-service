<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreasuryAccountsRequest;
use App\Http\Requests\UpdateTreasuryAccountsRequest;
use App\Imports\TreasuryAccountsImport;
use App\Models\TreasuryAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TreasuryAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected function success($data, string $message = 'Успешно', int $status = 200): JsonResponse
    {
        return response()->json([
            'message'   => $message,
            'data'      => $data,
            'timestamp' => now()->toIso8601ZuluString(),
            'success'   => true,
        ], $status);
    }

    protected function error(string $message, $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'message'   => $message,
            'data'      => $errors,
            'timestamp' => now()->toIso8601ZuluString(),
            'success'   => false,
        ], $status);
    }

    /**
     * GET /api/treasury-accounts
     */
    public function index(Request $request): JsonResponse
    {
        $qb = TreasuryAccount::query();

        foreach (['account', 'mfo', 'name', 'department', 'currency'] as $f) {
            if ($request->filled($f)) {
                $qb->where($f, $request->input($f));
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $qb->where(function($q) use ($s) {
                $q->where('account', 'LIKE', "%{$s}%")
                    ->orWhere('name',    'LIKE', "%{$s}%")
                    ->orWhere('department', 'LIKE', "%{$s}%");
            });
        }

        $sort      = $request->input('sort', 'account');
        $direction = $request->input('direction', 'asc');
        $allowed   = ['account','mfo','name','department','currency','created_at'];
        if (in_array($sort, $allowed)) {
            $qb->orderBy($sort, $direction);
        }

        $perPage   = (int) $request->input('per_page', 20);
        $paginated = $qb->simplePaginate($perPage);

        return $this->success($paginated);
    }

    /**
     * POST /api/treasury-accounts
     */
    public function store(StoreTreasuryAccountsRequest $request): JsonResponse
    {
        $model = TreasuryAccount::create(array_merge(
            $request->validated(),
            [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        ));

        return $this->success($model, 'Создано', 201);
    }

    /**
     * GET /api/treasury-accounts/{id}
     */
    public function show(string $id): JsonResponse
    {
        $account = TreasuryAccount::find($id);
        if (! $account) {
            return $this->error('Запись не найдена', [], 404);
        }

        return $this->success($account);
    }

    /**
     * PUT/PATCH /api/treasury-accounts/{id}
     */
    public function update(UpdateTreasuryAccountsRequest $request, TreasuryAccount $treasuryAccount): JsonResponse
    {
        $treasuryAccount->update(array_merge(
            $request->validated(),
            [
                'updated_by' => auth()->id(),
                'updated_at' => now()->toIso8601ZuluString(),
            ]
        ));

        return $this->success($treasuryAccount, 'Обновлено');
    }

    /**
     * DELETE /api/treasury-accounts/{id}
     */
    public function destroy(TreasuryAccount $treasuryAccount): JsonResponse
    {
        $treasuryAccount->delete();

        return $this->success([], 'Удалено');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $path = $request->file('file')->store('imports');
        $userId = $request->user()->id;

        Excel::import(new TreasuryAccountsImport($userId), $path);

        return $this->success([], 'Импорт Счетов казначейства запущен, проверьте очередь');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'treasury-accounts-'.now()->format('Ymd_His').'.xlsx';

        $generator = function() use ($request) {
            $query = TreasuryAccount::query();

            foreach (['account','mfo','name','department','currency'] as $f) {
                if ($request->filled($f)) {
                    $query->where($f, $request->input($f));
                }
            }
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function($q) use($s) {
                    $q->where('account','ILIKE',"%{$s}%")
                        ->orWhere('name','ILIKE',"%{$s}%");
                });
            }

            foreach ($query->orderBy('account')->cursor() as $ta) {
                yield [
                    'Account'      => $ta->account,
                    'MFO'          => $ta->mfo,
                    'Name'         => $ta->name,
                    'Department'   => $ta->department,
                    'Currency'     => $ta->currency,
                    'Created By'   => optional($ta->creator)->name,
                    'Updated By'   => optional($ta->editor)->name,
                    'Created At'   => $ta->created_at->toDateTimeString(),
                    'Updated At'   => $ta->updated_at->toDateTimeString(),
                ];
            }
        };

        return (new FastExcel($generator()))
            ->download($fileName);
    }
}
