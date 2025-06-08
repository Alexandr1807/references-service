<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBudgetHoldersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tin' => [
                'required',
                'string',
                'regex:/^\d{9}$/',
                'unique:budget_holders,tin'
            ],
            'name' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'responsible' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'tin.size' => 'ИНН должен быть ровно 9 цифр',
            'tin.unique' => 'Этот ИНН уже зарегистрирован',
            'name.required'  => 'Поле name обязательно',
            'region.required' => 'Поле region обязательно',
            'district.required' => 'Поле district обязательно',
            'address.required' => 'Поле address обязательно',
            'phone.required' => 'Поле phone обязательно',
            'responsible.required' => 'Поле responsible обязательно',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        throw new HttpResponseException(response()->json([
            'message'   => 'Ошибка валидации',
            'data'      => ['field' => $errors],
            'timestamp' => now()->toIso8601ZuluString(),
            'success'   => false,
        ], 422));
    }
}
