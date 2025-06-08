<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Passport\Client as BaseClient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PassportClient extends BaseClient
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
