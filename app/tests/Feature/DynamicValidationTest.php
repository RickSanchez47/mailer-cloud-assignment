<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeForm(array $schema): Form
    {
        $account = Account::factory()->create();
        $form = Form::factory()->for($account)->create();
        $version = FormVersion::factory()->for($form)->create([
            'version_number' => 1,
            'status' => 'published',
            'schema' => $schema,
        ]);
        $form->update(['published_version_id' => $version->id]);

        return $form->fresh(['account', 'publishedVersion']);
    }

    public function test_required_field_missing_is_rejected(): void
    {
        $form = $this->makeForm([
            'fields' => [
                ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ],
        ]);

        $this->postJson(
            "/api/public/{$form->account->api_key}/forms/{$form->slug}/submit",
            ['data' => []]
        )->assertStatus(422);
    }

    public function test_conditionally_required_field_is_enforced_server_side(): void
    {
        $form = $this->makeForm([
            'fields' => [
                [
                    'key' => 'source', 'type' => 'select', 'label' => 'Source',
                    'required' => true, 'options' => ['Ad', 'Friend'],
                ],
                [
                    'key' => 'ad_detail', 'type' => 'text', 'label' => 'Which ad?',
                    'required' => true,
                    'visible_if' => ['field' => 'source', 'equals' => 'Ad'],
                ],
            ],
        ]);

        // A malicious/buggy client could omit ad_detail while claiming it
        // was hidden; source=Ad makes it visible, so the server must
        // still require it independently of anything the client sent.
        $this->postJson(
            "/api/public/{$form->account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['source' => 'Ad']]
        )->assertStatus(422);

        $this->postJson(
            "/api/public/{$form->account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['source' => 'Friend']]
        )->assertStatus(201);
    }

    public function test_unknown_submitted_fields_are_rejected(): void
    {
        $form = $this->makeForm([
            'fields' => [
                ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ],
        ]);

        $this->postJson(
            "/api/public/{$form->account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['email' => 'a@example.com', 'injected_field' => 'x']]
        )->assertStatus(422);
    }

    public function test_stored_text_answers_are_stripped_of_html(): void
    {
        $form = $this->makeForm([
            'fields' => [
                ['key' => 'comment', 'type' => 'text', 'label' => 'Comment', 'required' => false],
            ],
        ]);

        $this->postJson(
            "/api/public/{$form->account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['comment' => '<script>alert(1)</script>hello']]
        )->assertStatus(201);

        $submission = $form->submissions()->first();
        $this->assertStringNotContainsString('<script>', $submission->data['comment']);
    }
}
