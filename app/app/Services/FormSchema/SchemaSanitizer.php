<?php

namespace App\Services\FormSchema;

/**
 * Form schemas are user-authored (a customer types field labels/help
 * text) and then rendered into a public, third-party page for anonymous
 * end-users. That makes the schema itself a stored-XSS surface, on top
 * of the submitted data — so it gets sanitized on the way in, same as
 * any other UGC.
 */
class SchemaSanitizer
{
    public function sanitize(array $schema): array
    {
        foreach ($schema['fields'] ?? [] as $i => $field) {
            $schema['fields'][$i]['label'] = $this->clean($field['label'] ?? '');
            $schema['fields'][$i]['help_text'] = $this->clean($field['help_text'] ?? '');
            // Field keys become JSON object keys and HTML `name` attributes —
            // restrict to a safe identifier charset.
            $schema['fields'][$i]['key'] = preg_replace('/[^a-zA-Z0-9_]/', '', $field['key'] ?? '');

            if (!empty($field['options'])) {
                $schema['fields'][$i]['options'] = array_map(
                    fn ($option) => $this->clean((string) $option),
                    $field['options']
                );
            }
        }

        return $schema;
    }

    protected function clean(string $value): string
    {
        return strip_tags(trim($value));
    }
}
