<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Sanitización XSS: Limpieza automática de campos sensibles.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('observaciones')) {
            $this->merge([
                'observaciones' => strip_tags((string)$this->observaciones)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nit' => 'sometimes|required|string',
            'numero_contrato' => 'sometimes|required|string',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }
}
