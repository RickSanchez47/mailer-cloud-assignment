<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Request $request, Form $form)
    {
        abort_if($form->account_id !== $request->user()->account_id, 403);

        $query = $form->submissions()->orderByDesc('created_at');

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        // Cursor pagination keeps this fast at high submission volume —
        // offset pagination degrades badly past page ~1000.
        return $query->cursorPaginate(50);
    }

    public function export(Request $request, Form $form): StreamedResponse
    {
        abort_if($form->account_id !== $request->user()->account_id, 403);

        return response()->streamDownload(function () use ($form) {
            $out = fopen('php://output', 'w');
            $headerWritten = false;

            // Chunked cursor over the FK index keeps memory flat no
            // matter how many submissions the form has accumulated.
            $form->submissions()->orderBy('id')->chunk(500, function ($chunk) use ($out, &$headerWritten) {
                foreach ($chunk as $submission) {
                    if (!$headerWritten) {
                        fputcsv($out, array_merge(['id', 'created_at'], array_keys($submission->data)));
                        $headerWritten = true;
                    }
                    fputcsv($out, array_merge([$submission->id, $submission->created_at], array_values($submission->data)));
                }
            });

            fclose($out);
        }, "form-{$form->id}-submissions.csv");
    }
}
