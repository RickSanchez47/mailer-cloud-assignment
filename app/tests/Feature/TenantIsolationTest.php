<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_forms_slug_cannot_be_resolved_under_another_accounts_api_key(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();
        $formA = Form::factory()->for($accountA)->create();

        // Requesting account A's slug scoped under account B's public key
        // must 404 — never silently fall back to a cross-tenant match.
        $this->getJson("/api/public/{$accountB->api_key}/forms/{$formA->slug}")
            ->assertStatus(404);
    }
}
