<?php
namespace App\Imports;

use App\Models\SwiftCode;
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

class SwiftCodesImport implements
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
        return new SwiftCode([
            'id'         => (string) Str::uuid(),
            'swift_code'=> $row['swift_code'],
            'bank_name' => $row['bank_name'],
            'country'    => $row['country'],
            'city'       => $row['city'],
            'address'    => $row['address'],
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.swift_code' => [
                'required','string',
                'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/',
                'unique:swift_codes,swift_code',
            ],
            '*.bank_name' => 'required|string|max:255',
            '*.country'   => 'required|string',
            '*.city'      => 'required|string|max:255',
            '*.address'   => 'required|string|max:255',
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
