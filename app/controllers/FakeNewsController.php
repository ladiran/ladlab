<?php
declare(strict_types=1);

/** Public "Fake news checker" page, and the small endpoint its script talks to. */
final class FakeNewsController
{
    public function index(): void
    {
        if (!module_enabled('fakenews')) {
            abort(404);
        }
        view('public/fakenews', [
            'title' => 'Fake news checker',
            'meta_desc' => 'Paste a headline, a link or a social media handle and see whether it is verified fake, unverified or authenticated, with the evidence shown.',
            'online' => FakeNews::online(),
        ]);
    }

    /** Receives JSON from the page script, forwards it to the checker, returns the cleaned verdict. */
    public function check(): void
    {
        if (!module_enabled('fakenews')) {
            json_out(['error' => 'The checker is switched off.'], 404);
        }
        csrf_check(); // the script sends the token in the X-CSRF-Token header
        if (!rate_ok('fakenews', 20, 3600)) {
            json_out(['error' => 'You have reached the limit of 20 checks per hour. Please try again later.'], 429);
        }
        $raw = (string)file_get_contents('php://input', false, null, 0, 8193);
        if (strlen($raw) > 8192) {
            json_out(['error' => 'That request is too large.'], 413);
        }
        $in = json_decode($raw, true);
        if (!is_array($in)) {
            json_out(['error' => 'Please send your check as a form.'], 400);
        }
        $limits = ['title' => 500, 'url' => 2000, 'handle' => 300];
        $payload = [];
        foreach ($limits as $k => $max) {
            $v = $in[$k] ?? '';
            $v = is_string($v) ? trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '') : '';
            if ($v !== '') {
                $payload[$k] = mb_substr($v, 0, $max);
            }
        }
        if (!$payload) {
            json_out(['error' => 'Please enter a headline, a link or a social media handle.'], 422);
        }
        $r = FakeNews::check($payload);
        if (!$r['ok']) {
            json_out(['error' => $r['error']], $r['status']);
        }
        json_out($r['data']);
    }
}
