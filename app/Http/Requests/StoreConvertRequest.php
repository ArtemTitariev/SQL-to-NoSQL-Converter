<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvertRequest extends FormRequest
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
            'sql_database.driver' => 'required|string|max:10',
            'sql_database.host' => 'required|string',
            'sql_database.port' => 'required|integer|gte:0|lte:65535',
            'sql_database.database' => 'required|string',
            'sql_database.username' => 'required|string',
            'sql_database.password' => 'nullable|string',
            'sql_database.charset' => 'required|string|max:10',

            'sql_database.collation' => 'nullable|string',
            'sql_database.search_path' => 'nullable|string',
            'sql_database.sslmode' => 'nullable|string',

            
            'mongo_database.dsn' => 'required|string',
            'mongo_database.database' => 'required|string',

            'description' => 'nullable|string',
        ];
    }
}
