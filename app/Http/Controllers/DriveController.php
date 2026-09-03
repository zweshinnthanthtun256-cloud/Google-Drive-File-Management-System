<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Drive Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $service = new GoogleDriveService(
            $request->user()
        );

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $from = $request->input('from');

        $to = $request->input('to');

        $search = $request->input('search');

        $folderId = $request->input('folder_id');

        $type = $request->input('type');

        $pageToken = $request->input('page_token');

        /*
        |--------------------------------------------------------------------------
        | Per Page
        |--------------------------------------------------------------------------
        */

        $perPage = (int) $request->input(
            'per_page',
            10
        );

        /*
        |--------------------------------------------------------------------------
        | Security: allowed page sizes
        |--------------------------------------------------------------------------
        */

        if (! in_array(
            $perPage,
            [10, 25, 50, 100],
            true
        )) {
            $perPage = 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Files
        |--------------------------------------------------------------------------
        */

        $fileList = $service->listFiles(
            $from,
            $to,
            $search,
            $folderId,
            $type,
            $pageToken,
            $perPage
        );

        $files = $fileList->getFiles();

        $nextPageToken =
            $fileList->getNextPageToken();

        /*
        |--------------------------------------------------------------------------
        | Get Folders
        |--------------------------------------------------------------------------
        */

        $folders = $service->listFolders();

        /*
        |--------------------------------------------------------------------------
        | Return Inertia Page
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'Drive/Index',
            [
                'files' => collect($files)
                    ->map(function ($file) {
                        return [
                            'id' => $file->getId(),

                            'name' => $file->getName(),

                            'mimeType' => $file->getMimeType(),

                            'size' => $file->getSize(),

                            'createdTime' => $file->getCreatedTime(),

                            'modifiedTime' => $file->getModifiedTime(),

                            'parents' => $file->getParents(),

                            'canDownload' => $file
                                ->getCapabilities()
                                ?->getCanDownload(),

                            'canDelete' => $file
                                ->getCapabilities()
                                ?->getCanDelete(),
                        ];
                    })
                    ->values(),

                'folders' => collect($folders)
                    ->map(function ($folder) {
                        return [
                            'id' => $folder->getId(),

                            'name' => $folder->getName(),
                        ];
                    })
                    ->values(),

                /*
                |--------------------------------------------------------------------------
                | Send current filters back to Vue
                |--------------------------------------------------------------------------
                */

                'filters' => [
                    'from' => $from,

                    'to' => $to,

                    'search' => $search,

                    'folder_id' => $folderId,

                    'type' => $type,

                    'page_token' => $pageToken,

                    'per_page' => $perPage,
                ],

                /*
                |--------------------------------------------------------------------------
                | Pagination
                |--------------------------------------------------------------------------
                */

                'pagination' => [
                    'nextPageToken' => $nextPageToken,

                    'currentPageToken' => $pageToken,
                ],
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Download
    |--------------------------------------------------------------------------
    */

    public function download(
        Request $request,
        string $fileId
    ) {
        $service = new GoogleDriveService(
            $request->user()
        );

        /*
        |--------------------------------------------------------------------------
        | Get File
        |--------------------------------------------------------------------------
        */

        $file = $service->getFile(
            $fileId
        );

        /*
        |--------------------------------------------------------------------------
        | Permission Check
        |--------------------------------------------------------------------------
        */

        $capabilities =
            $file->getCapabilities();

        if (
            $capabilities &&
            ! $capabilities->getCanDownload()
        ) {
            abort(
                403,
                'This file cannot be downloaded.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Download from Google Drive
        |--------------------------------------------------------------------------
        */

        $response =
            $service->downloadFile(
                $fileId
            );

        /*
        |--------------------------------------------------------------------------
        | Stream Response
        |--------------------------------------------------------------------------
        */

        return new StreamedResponse(
            function () use ($response) {
                while (
                    ! $response
                        ->getBody()
                        ->eof()
                ) {
                    echo $response
                        ->getBody()
                        ->read(
                            1024 * 1024
                        );

                    flush();
                }
            },

            200,

            [
                'Content-Type' => $file->getMimeType()
                    ?: 'application/octet-stream',

                'Content-Disposition' => 'attachment; filename="'.
                    addslashes(
                        $file->getName()
                    ).
                    '"',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */

    public function upload(
        Request $request
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'file' => [
                'required',

                'file',

                /*
                |--------------------------------------------------------------------------
                | 512000 KB = 500 MB
                |--------------------------------------------------------------------------
                */

                'max:512000',
            ],

            'folder_id' => [
                'nullable',

                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Uploaded File
        |--------------------------------------------------------------------------
        */

        $uploadedFile =
            $request->file('file');

        /*
        |--------------------------------------------------------------------------
        | Google Drive Service
        |--------------------------------------------------------------------------
        */

        $service = new GoogleDriveService(
            $request->user()
        );

        /*
        |--------------------------------------------------------------------------
        | Upload to Google Drive
        |--------------------------------------------------------------------------
        */

        $file = $service->uploadFile(
            $uploadedFile->getRealPath(),

            $uploadedFile
                ->getClientOriginalName(),

            $uploadedFile->getMimeType()
                ?: 'application/octet-stream',

            $request->input(
                'folder_id'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',

            "File '{$file->getName()}' uploaded successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(
        Request $request,
        string $fileId
    ) {
        $service = new GoogleDriveService(
            $request->user()
        );

        /*
        |--------------------------------------------------------------------------
        | Get File
        |--------------------------------------------------------------------------
        */

        $file = $service->getFile(
            $fileId
        );

        /*
        |--------------------------------------------------------------------------
        | Check Permission
        |--------------------------------------------------------------------------
        */

        $capabilities =
            $file->getCapabilities();

        if (
            $capabilities &&
            ! $capabilities->getCanDelete()
        ) {
            abort(
                403,
                'You do not have permission to delete this file.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete from Google Drive
        |--------------------------------------------------------------------------
        */

        $service->deleteFile(
            $fileId
        );

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',

            "File '{$file->getName()}' deleted successfully."
        );
    }
}
