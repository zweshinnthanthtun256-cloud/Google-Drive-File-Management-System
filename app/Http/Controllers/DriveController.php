<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveController extends Controller
{
    public function index(Request $request)
{
    $service = new GoogleDriveService(
        $request->user()
    );

    $pageToken = $request->input('page_token');

    $fileList = $service->listFiles(
        $request->input('from'),
        $request->input('to'),
        $request->input('search'),
        $request->input('folder_id'),
        $pageToken,
        15 // Page တစ်ခုမှာ ပြသချင်သည့် အရေအတွက်
    );

    $files = $fileList->getFiles();
    $nextPageToken = $fileList->getNextPageToken();
    $folders = $service->listFolders();

    return Inertia::render('Drive/Index', [
        'files' => collect($files)->map(function ($file) {
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'createdTime' => $file->getCreatedTime(),
                'modifiedTime' => $file->getModifiedTime(),
                'parents' => $file->getParents(),
                'canDownload' => $file->getCapabilities()?->getCanDownload(),
            ];
        })->values(),

        'folders' => collect($folders)->map(function ($folder) {
            return [
                'id' => $folder->getId(),
                'name' => $folder->getName(),
            ];
        })->values(),

        'filters' => [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'search' => $request->input('search'),
            'folder_id' => $request->input('folder_id'),
            'page_token' => $pageToken,
        ],

        // Pagination Meta Information ကို Front-end ဆီ ပို့ပေးခြင်း
        'pagination' => [
            'nextPageToken' => $nextPageToken,
            'currentPageToken' => $pageToken,
        ],
    ]);
}
    public function download(
        Request $request,
        string $fileId
    ) {
        $service = new GoogleDriveService(
            $request->user()
        );

        $file = $service->getFile($fileId);

        if (
            $file->getCapabilities()
            && ! $file->getCapabilities()->getCanDownload()
        ) {
            abort(403, 'This file cannot be downloaded.');
        }

        $response = $service->downloadFile($fileId);

        return new StreamedResponse(
            function () use ($response) {
                while (! $response->getBody()->eof()) {
                    echo $response->getBody()->read(1024 * 1024);

                    flush();
                }
            },
            200,
            [
                'Content-Type' => $file->getMimeType()
                    ?: 'application/octet-stream',

                'Content-Disposition' => 'attachment; filename="'.
                    addslashes($file->getName()).
                    '"',
            ]
        );
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:512000',
            ],

            'folder_id' => [
                'nullable',
                'string',
            ],
        ]);

        $uploadedFile = $request->file('file');

        $service = new GoogleDriveService(
            $request->user()
        );

        $file = $service->uploadFile(
            $uploadedFile->getRealPath(),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile->getMimeType()
                ?: 'application/octet-stream',
            $request->input('folder_id')
        );

        return back()->with(
            'success',
            "File '{$file->getName()}' uploaded successfully."
        );
    }

    public function delete(Request $request,string $fileId)
     {
        $service = new GoogleDriveService(
            $request->user()
        );

        // Get file first
        $file = $service->getFile($fileId);

        // Check whether user can delete the file
        $capabilities = $file->getCapabilities();

        if ($capabilities && ! $capabilities->getCanDelete()) {
            abort(403, 'You do not have permission to delete this file.');
        }

        // Delete from Google Drive
        $service->deleteFile($fileId);

        return back()->with(
            'success',
            "File '{$file->getName()}' deleted successfully."
        );
    }
}
