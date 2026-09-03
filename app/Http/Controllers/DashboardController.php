<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Create Google Drive Service
        |--------------------------------------------------------------------------
        */

        $service = new GoogleDriveService(
            $request->user()
        );

        /*
        |--------------------------------------------------------------------------
        | Get Dashboard Data
        |--------------------------------------------------------------------------
        */

        $dashboard =
            $service->getDashboardData();

        /*
        |--------------------------------------------------------------------------
        | Return Dashboard
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'Dashboard',
            [
                'stats' =>
                    $dashboard['stats'],

                'recentFiles' =>
                    $dashboard['recentFiles'],
            ]
        );
    }
}