<?php

namespace App\Services\FormSchema;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Builds and runs Laravel validation rules derived entirely from a
 * published form-version's schema. This is the server-side re-derivation
 * of validation and conditional-visibility logic — the client's rendered
 * form (and any "hidden" state it computed) is never trusted.
 */
class DynamicValidator
{
    public function validate(array $schema, array $input): ValidatorInstance
    {
        $fields = $schema['fields'] ?? [];
        $rules = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $rules["data.$key"] = $this->rulesForField($field, $input['data'] ?? []);

            if (in_array($field['type'], ['multi-select', 'checkbox'], true)) {
                $options = $field['options'] ?? [];
                $rules["data.$key.*"] = ['in:' . implode(',', $options)];
            }
        }

        $validator = Validator::make($input, $rules);

        // Reject any submitted key that isn't part of the schema at all —
        // a client should never be able to smuggle extra fields through.
        $allowedKeys = collect($fields)->pluck('key')->all();
        $submittedKeys = array_keys($input['data'] ?? []);
        $unknown = array_diff($submittedKeys, $allowedKeys);

        if (!empty($unknown)) {
            $validator->after(function (ValidatorInstance $v) use ($unknown) {
                $v->errors()->add('data', 'Unknown field(s) submitted: ' . implode(', ', $unknown));
            });
        }

        return $validator;
    }

    protected function rulesForField(array $field, array $data): array
    {
        $isVisible = $this->isVisible($field, $data);

        // A field only becomes "required" once its own visibility
        // condition is satisfied by what was actually submitted —
        // recomputed here, never taken from the client.
        $rules = (!empty($field['required']) && $isVisible) ? ['required'] : ['nullable'];

        switch ($field['type']) {
            case 'text':
                $rules[] = 'string';
                $rules[] = 'max:5000';
                break;
            case 'email':
                $rules[] = 'string';
                $rules[] = 'email:rfc';
                $rules[] = 'max:255';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'select':
            case 'radio':
                $rules[] = 'string';
                $rules[] = 'in:' . implode(',', $field['options'] ?? []);
                break;
            case 'multi-select':
            case 'checkbox':
                $rules[] = 'array';
                break;
            default:
                $rules[] = 'string';
        }

        return $rules;
    }

    protected function isVisible(array $field, array $data): bool
    {
        $condition = $field['visible_if'] ?? null;
        if (!$condition) {
            return true;
        }

        $dependsOnValue = $data[$condition['field']] ?? null;

        return $dependsOnValue == $condition['equals'];
    }
}
