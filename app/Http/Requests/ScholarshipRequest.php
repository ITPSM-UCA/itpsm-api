<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScholarshipRequest extends FormRequest
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
            'scholarship_foundation' => 'required|string|max:255'
        ];
    }

    public function messages()
  {
    return [
      'name.required' => 'El campo nombre es obligatorio',
      'scholarship_foundation.required' => 'El campo scholarship_foundation es obligatorio'
    ];
  }
}