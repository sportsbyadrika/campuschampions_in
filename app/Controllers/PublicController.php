<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Public results page (no login required). Full implementation lands with the
 * results module; this placeholder keeps the route valid.
 */
class PublicController extends Controller
{
    public function results(): void
    {
        $this->view('public/results', [
            'title' => 'Public Results',
        ], 'layouts/public');
    }
}
