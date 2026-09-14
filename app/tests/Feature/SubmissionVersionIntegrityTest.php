<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionVersionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_republishing_a_form_does_not_reattach_prior_submissions_to_the_new_version(): void
    {
        $account = Account::factory()->create();
        $form = Form::factory()->for($account)->create();

        $v1 = FormVersion::factory()->for($form)->create([
            'version_number' => 1,
            'status' => 'published',
            'schema' => ['fields' => [
                ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ]],
        ]);
        $form->update(['published_version_id' => $v1->id]);

        $this->postJson(
            "/api/public/{$account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['email' => 'a@example.com']]
        )->assertStatus(201);

        $submission = $form->fresh()->submissions()->first();
        $this->assertEquals($v1->id, $submission->form_version_id);

        // Publish a new version that renames the field entirely.
        $v2 = FormVersion::factory()->for($form)->create([
            'version_number' => 2,
            'status' => 'published',
            'schema' => ['fields' => [
                ['key' => 'email_address', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ]],
        ]);
        $form->update(['published_version_id' => $v2->id]);

        $submission->refresh();

        // The old submission must still be interpretable against v1 —
        // it is never silently reattached to v2's (incompatible) schema.
        $this->assertEquals($v1->id, $submission->form_version_id);
        $this->assertArrayHasKey('email', $submission->data);

        $this->postJson(
            "/api/public/{$account->api_key}/forms/{$form->slug}/submit",
            ['data' => ['email_address' => 'b@example.com']]
        )->assertStatus(201);

        $latest = $form->fresh()->submissions()->orderByDesc('id')->first();
        $this->assertEquals($v2->id, $latest->form_version_id);
        $this->assertArrayHasKey('email_address', $latest->data);
    }
}
