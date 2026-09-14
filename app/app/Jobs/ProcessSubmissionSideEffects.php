<?php

namespace App\Jobs;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Anything that is NOT required for "the submission is durably stored" —
 * customer webhooks, notification emails, search-index updates for the
 * dashboard, etc. Failure here retries independently and never risks the
 * submission itself, since it's already committed before this is dispatched.
 */
class ProcessSubmissionSideEffects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(public int $submissionId)
    {
    }

    public function handle(): void
    {
        $submission = Submission::find($this->submissionId);
        if (!$submission) {
            return;
        }

        // Placeholder for customer webhook delivery, notification email,
        // search-index upsert, etc. Intentionally left as a stub — see
        // ARCHITECTURE.md "Built vs. Designed".
    }
}
