<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTreasuryAccountsRequest extends FormRequest
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
            'account'    => ['sometimes', 'required', 'digits:20'],
            'mfo'        => ['sometimes', 'required', 'digits:5'],
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'department' => ['sometimes', 'required', 'string', 'max:255'],
            'currency'   => ['sometimes', 'required', 'string', 'size:3', 'alpha', 'uppercase'],
        ];
    }

    public function messages(): array
    {
        return [
            'account.required'   => 'Поле account обязательно',
            'account.digits'     => 'Поле account должно состоять из 20 цифр',

            'mfo.required'       => 'Поле mfo обязательно',
            'mfo.digits'         => 'Поле mfo должно состоять из 5 цифр',

            'name.required'      => 'Поле name обязательно',
            'name.string'        => 'Поле name должно быть строкой',

            'department.required'=> 'Поле department обязательно',
            'department.string'  => 'Поле department должно быть строкой',

            'currency.required'  => 'Поле currency обязательно',
            'currency.string'    => 'Поле currency должно быть строкой',
            'currency.size'      => 'Поле currency должно состоять из 3 символов',
            'currency.alpha'     => 'Поле currency должно содержать только буквы',
            'currency.uppercase' => 'Поле currency должно быть в верхнем регистре',
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
