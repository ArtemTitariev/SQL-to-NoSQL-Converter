<?php

namespace App\Http\Requests;

use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\Validator;

class UpdateRelationshipRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    // protected $stopOnFirstFailure = true;

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
            'relationData' => 'required|string',
            'relationTypeLinkEmbedd' => 'nullable|string|required_without:relationTypeManyToMany',
            'relationTypeManyToMany' => 'nullable|string|required_without:relationTypeLinkEmbedd',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $relationData = $this->input('relationData');

                if ($relationData) {
                    try {
                        $data = json_decode(decrypt($relationData), true);
                        $this->merge(['decodedRelationData' => $data]);
                    } catch (DecryptException $e) {
                        $validator->errors()->add('relationData', __('validation.failed_to_decrypt'));
                        return;
                    }

                    if (isset($data['model'])) {
                        if ($data['model'] === LinkEmbedd::class && !$this->filled('relationTypeLinkEmbedd')) {
                            $validator->errors()->add('relationTypeLinkEmbedd', __('validation.required', ['attribute' => 'Relation Type Link Embedd']));
                        } elseif ($data['model'] === ManyToManyLink::class && !$this->filled('relationTypeManyToMany')) {
                            $validator->errors()->add('relationTypeManyToMany', __('validation.required', ['attribute' => 'Relation Type Many To Many']));
                        }
                    }
                }
            }
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
