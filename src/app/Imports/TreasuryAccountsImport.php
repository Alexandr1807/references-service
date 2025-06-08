<?php

namespace App\Imports;

use App\Models\TreasuryAccount;
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

class TreasuryAccountsImport implements
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

    /**
     * Map a row to a TreasuryAccount model.
     */
    public function model(array $row)
    {
        Log::info('Import Treasury row', $row);

        return new TreasuryAccount([
            'id'          => (string) Str::uuid(),
            'account'     => $row['account'],
            'mfo'         => $row['mfo'],
            'name'        => $row['name'],
            'department'  => $row['department'],
            'currency'    => $row['currency'],
            'created_by'  => $this->userId,
            'updated_by'  => $this->userId,
        ]);
    }

    /**
     * Validation rules for each row.
     */
    public function rules(): array
    {
        return [
            '*.account'    => ['required', 'digits:20', 'unique:treasury_accounts,account'],
            '*.mfo'        => ['required', 'digits:5'],
            '*.name'       => ['required', 'string', 'max:255'],
            '*.department' => ['required', 'string', 'max:255'],
            '*.currency'   => ['required', 'string', 'size:3', 'alpha', 'uppercase'],
        ];
    }

    /**
     * Handle validation failures.
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('Treasury import validation failed', [
                'row'    => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Number of rows per chunk.
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
