<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSwiftCodeRequest;
use App\Http\Requests\UpdateSwiftCodeRequest;
use App\Imports\SwiftCodesImport;
use App\Models\SwiftCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SwiftCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // Общий “успешный” ответ
    protected function success($data, string $message = 'Успешно', int $status = 200): JsonResponse
    {
        return response()->json([
            'message'   => $message,
            'data'      => $data,
            'timestamp' => now()->toIso8601ZuluString(),
            'success'   => true,
        ], $status);
    }

    // Общий “ошибочный” ответ
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
        $qb = SwiftCode::query();

        foreach (['country','city','bank_name', 'swift_code'] as $f) {
            if ($request->filled($f)) {
                $qb->where($f, $request->input($f));
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $qb->where(function($q) use($s) {
                $q->where('bank_name','ILIKE',"%{$s}%")
                    ->orWhere('swift_code','ILIKE',"%{$s}%")
                    ->orWhere('city','ILIKE',"%{$s}%");
            });
        }

        $sort = $request->input('sort','swift_code');
        $direction = $request->input('direction','asc');
        $allowed = ['swift_code','bank_name','country','city','created_at'];
        if (in_array($sort, $allowed)) {
            $qb->orderBy($sort,$direction);
        }

        $perPage = (int) $request->input('per_page',20);
        $paginated = $qb->simplePaginate($perPage);

        return $this->success($paginated);
    }

    public function store(StoreSwiftCodeRequest $r): JsonResponse
    {
        $model = SwiftCode::create(array_merge(
            $r->validated(),
            ['created_by'=>auth()->id(), 'updated_by'=>auth()->id()]
        ));
        return $this->success($model, 'Создано', 201);
    }

    public function show($id): JsonResponse
    {
        $swift = SwiftCode::find($id);

        if (!$swift) {
            return $this->error('Запись не найдена', [], 404);
        }

        return $this->success($swift);
    }

    public function update(UpdateSwiftCodeRequest $r, SwiftCode $swiftCode): JsonResponse
    {
        $swiftCode->update(array_merge(
            $r->validated(),
            ['updated_by'=>auth()->id(), 'updated_at' => now()->toIso8601ZuluString()]
        ));
        return $this->success($swiftCode, 'Обновлено');
    }

    public function destroy(SwiftCode $swiftCode): JsonResponse
    {
        $swiftCode->delete();
        return $this->success([], 'Удалено', 200);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $path = $request->file('file')->store('imports');
        $userId = $request->user()->id;

        Excel::import(new SwiftCodesImport($userId), $path);

        return $this->success([], 'Импорт SWIFT-кодов запущен, проверьте очередь');
    }
}
