<?php

namespace App\Http\Requests;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
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

                $data = $this->tryDecrypt($relationData, $validator);
                if (!$data) {
                    return;
                }

                $this->merge(['decodedRelationData' => $data]);
                $this->validateModel($data['model'], $validator);
            }
        ];
    }

    protected function tryDecrypt($relationData, $validator)
    {
        try {
            return json_decode(decrypt($relationData), true);
        } catch (DecryptException $e) {
            $validator->errors()->add('relationData', __('validation.failed_to_decrypt'));
            return null;
        }
    }

    protected function validateModel($model, $validator)
    {
        if ($model === LinkEmbedd::class) {
            $this->validateLinkEmbedd($validator);
        } elseif ($model === ManyToManyLink::class) {
            $this->validateManyToMany($validator);
        }
    }

    protected function validateLinkEmbedd($validator)
    {
        if (
            !$this->filled('relationTypeLinkEmbedd') ||
            !MongoRelationType::tryFrom($this->relationTypeLinkEmbedd)
        ) {
            $validator->errors()->add(
                'relationTypeLinkEmbedd',
                __('validation.required', ['attribute' => 'Relation Type Link Embedd'])
            );
        }
    }

    protected function validateManyToMany($validator)
    {
        if (
            !$this->filled('relationTypeManyToMany') ||
            !MongoManyToManyRelation::tryFrom($this->relationTypeManyToMany)
        ) {
            $validator->errors()->add(
                'relationTypeManyToMany',
                __('validation.required', ['attribute' => 'Relation Type Many To Many'])
            );
        }
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
            'type' => 'validation_error',
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
