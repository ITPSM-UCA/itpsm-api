<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:6',
        ];
    }

    public function messages()
    {
      return [
          'name.required' => 'El campo nombre es obligatorio',
          'name.string' => 'El campo nombre debe ser una cadena de texto',
          'name.max' => 'El campo nombre solo permite 255 caracteres máximo',
          'code.required' => 'El campo codigo es obligatorio',
          'code.string' => 'El campo codigo debe ser una cadena de texto',
          'code.max' => 'El campo codigo permite 6 caracteres máximo'
      ];
    }
}
