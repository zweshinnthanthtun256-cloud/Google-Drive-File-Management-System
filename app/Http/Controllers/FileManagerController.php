<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class FileManagerController extends Controller
{
    public function index()
{
    \Log::info('STEP 1: Controller reached');

    $response = Inertia::render('Drive/Index');

    \Log::info('STEP 2: Inertia render created');

    return $response;
}
}