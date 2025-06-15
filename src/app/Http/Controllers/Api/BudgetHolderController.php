<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetHoldersRequest;
use App\Http\Requests\UpdateBudgetHoldersRequest;
use App\Imports\BudgetHoldersImport;
use App\Models\BudgetHolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BudgetHolderController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        $qb = BudgetHolder::query();

        foreach (['tin','name', 'region', 'district', 'address', 'phone', 'responsible'] as $f) {
            if ($request->filled($f)) {
                $qb->where($f, $request->input($f));
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $qb->where(function($q) use($s) {
                $q->where('tin','ILIKE',"%{$s}%")
                    ->orWhere('name','ILIKE',"%{$s}%")
                    ->orWhere('region','ILIKE',"%{$s}%")
                    ->orWhere('district','ILIKE',"%{$s}%")
                    ->orWhere('address','ILIKE',"%{$s}%")
                    ->orWhere('phone','ILIKE',"%{$s}%")
                    ->orWhere('responsible','ILIKE',"%{$s}%");
            });
        }

        $sort = $request->input('sort','name');
        $direction = $request->input('direction','asc');
        $allowed   = ['tin','name','region','district','address','phone','responsible'];
        if (in_array($sort, $allowed)) {
            $qb->orderBy($sort,$direction);
        }

        $perPage = (int) $request->input('per_page',20);
        $paginated = $qb->simplePaginate($perPage);

        return $this->success($paginated);
    }

    public function store(StoreBudgetHoldersRequest $r): JsonResponse
    {
        $model = BudgetHolder::create(array_merge(
            $r->validated(),
            ['created_by'=>auth()->id(), 'updated_by'=>auth()->id()]
        ));
        return $this->success($model, 'Создано', 201);
    }

    public function show($id): JsonResponse
    {
        $budgetHolder = BudgetHolder::find($id);

        if (!$budgetHolder) {
            return $this->error('Запись не найдена', [], 404);
        }

        return $this->success($budgetHolder);
    }

    public function update(UpdateBudgetHoldersRequest $r, BudgetHolder $budgetHolder): JsonResponse
    {
        $budgetHolder->update(array_merge(
            $r->validated(),
            ['updated_by'=>auth()->id(), 'updated_at' => now()->toIso8601ZuluString()]
        ));
        return $this->success($budgetHolder, 'Обновлено');
    }

    public function destroy(BudgetHolder $budgetHolder): JsonResponse
    {
        $budgetHolder->delete();
        return $this->success([], 'Удалено', 200);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $path = $request->file('file')->store('imports');
        $userId = $request->user()->id;

        Log::info('Starting import: ', ['path' => $path, 'user' => $userId]);

        Excel::queueImport(new BudgetHoldersImport($userId), $path);

        Log::info('Import finished');
        return $this->success([], 'Импорт бюджетополучателей запущен, проверьте очередь');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'budget-holders-'.now()->format('Ymd_His').'.xlsx';

        $rows = function() use ($request) {
            $query = BudgetHolder::query();

            foreach (['tin','name','region','district','phone','responsible'] as $f) {
                if ($request->filled($f)) {
                    $query->where($f, $request->input($f));
                }
            }
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function($q) use ($s) {
                    $q->where('tin','ILIKE',"%{$s}%")
                        ->orWhere('name','ILIKE',"%{$s}%")
                        ->orWhere('region','ILIKE',"%{$s}%")
                        ->orWhere('district','ILIKE',"%{$s}%")
                        ->orWhere('phone','ILIKE',"%{$s}%")
                        ->orWhere('responsible','ILIKE',"%{$s}%");
                });
            }

            foreach ($query->orderBy('name')->cursor() as $bh) {
                yield [
                    'ИНН'           => $bh->tin,
                    'Наименование'          => $bh->name,
                    'Регион'        => $bh->region,
                    'Район'      => $bh->district,
                    'Адрес'       => $bh->address,
                    'Телефон'         => $bh->phone,
                    'Ответственный'   => $bh->responsible,
                ];
            }
        };

        return (new FastExcel($rows()))
            ->download($fileName);
    }

}
