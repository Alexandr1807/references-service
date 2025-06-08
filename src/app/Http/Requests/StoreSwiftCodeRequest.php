<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSwiftCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'swift_code' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/',
                'unique:swift_codes,swift_code',
            ],
            'bank_name'  => 'required|string|max:255',
            'country'    => 'required|string',
            'city'       => 'required|string|max:255',
            'address'    => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'swift_code.size' => 'SWIFT-код должен быть ровно 8 или 11 символов.',
            'swift_code.unique' => 'Этот SWIFT-код уже зарегистрирован',
            'bank_name.required'  => 'Поле bank_name обязательно',
            'swift_code.required' => 'Поле swift_code обязательно',
            'country.required' => 'Поле country обязательно',
            'city.required' => 'Поле city обязательно',
            'address.required' => 'Поле address обязательно',
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
