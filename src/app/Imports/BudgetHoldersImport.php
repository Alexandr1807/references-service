<?php

namespace App\Imports;

use App\Models\BudgetHolder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class BudgetHoldersImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    ShouldQueueWithoutChain,
    SkipsOnFailure
{
    use SkipsFailures;

    protected string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function model(array $row)
    {
        return new BudgetHolder([
            'id'          => (string) Str::uuid(),
            'tin'         => $row['tin'],
            'name'        => $row['name'],
            'region'      => $row['region'],
            'district'    => $row['district'],
            'address'     => $row['address'],
            'phone'       => (string) $row['phone'],
            'responsible' => $row['responsible'],
            'created_by'  => $this->userId,
            'updated_by'  => $this->userId,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.tin'         => ['required','digits:9','unique:budget_holders,tin'],
            '*.name'        => 'required|string|max:255',
            '*.region'      => 'required|string|max:255',
            '*.district'    => 'required|string|max:255',
            '*.address'     => 'required|string|max:255',
            '*.phone'       => ['required','digits_between:7,15'],
            '*.responsible' => 'required|string|max:255',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('Import validation failed', [
                'row'    => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
