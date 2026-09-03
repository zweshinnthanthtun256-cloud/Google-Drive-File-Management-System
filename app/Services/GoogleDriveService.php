<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use RuntimeException;

class GoogleDriveService
{
    protected User $user;

    protected Client $client;

    protected Drive $drive;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        User $user
    ) {
        $this->user = $user;

        /*
        |--------------------------------------------------------------------------
        | Google Client
        |--------------------------------------------------------------------------
        */

        $this->client = new Client;

        $this->client->setClientId(
            config('services.google.client_id')
        );

        $this->client->setClientSecret(
            config('services.google.client_secret')
        );

        $this->client->setAccessType(
            'offline'
        );

        $this->client->setScopes([
            Drive::DRIVE,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load User Token
        |--------------------------------------------------------------------------
        */

        $accessToken = $user->google_token;

        $refreshToken = $user->google_refresh_token;

        if (! $accessToken) {
            throw new RuntimeException(
                'Google Drive is not connected.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Set Access Token
        |--------------------------------------------------------------------------
        */

        $this->client->setAccessToken([
            'access_token' => $accessToken,

            'refresh_token' => $refreshToken,

            'expires_in' => 3600,

            'created' => $user->google_token_expires_at
                ? now()
                    ->subSeconds(
                        max(
                            0,
                            3600 -
                            now()->diffInSeconds(
                                $user->google_token_expires_at
                            )
                        )
                    )
                    ->timestamp
                : now()->timestamp,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Refresh Token
        |--------------------------------------------------------------------------
        */

        if (
            $this->client->isAccessTokenExpired()
        ) {

            if (! $refreshToken) {
                throw new RuntimeException(
                    'Google refresh token is missing. Please connect Google Drive again.'
                );
            }

            $newToken =
                $this->client
                    ->fetchAccessTokenWithRefreshToken(
                        $refreshToken
                    );

            if (
                isset($newToken['error'])
            ) {
                throw new RuntimeException(
                    'Unable to refresh Google access token.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Save New Token
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $newToken['access_token']
                )
            ) {
                $user->google_token =
                    $newToken['access_token'];
            }

            if (
                isset(
                    $newToken['expires_in']
                )
            ) {
                $user->google_token_expires_at =
                    now()->addSeconds(
                        $newToken['expires_in']
                    );
            }

            $user->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Google Drive API
        |--------------------------------------------------------------------------
        */

        $this->drive = new Drive(
            $this->client
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LIST FILES
    |--------------------------------------------------------------------------
    |
    | Root Directory:
    |     Returns ALL files from the entire Google Drive.
    |
    | Folder selected:
    |     Returns files inside that folder only.
    |
    | Folders themselves are excluded.
    |
    */

    public function listFiles(
        ?string $from = null,
        ?string $to = null,
        ?string $search = null,
        ?string $folderId = null,
        ?string $type = null,
        ?string $pageToken = null,
        int $pageSize = 10
    ) {
        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        if (! in_array(
            $pageSize,
            [10, 25, 50, 100],
            true
        )) {
            $pageSize = 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = [
            'trashed = false',
        ];

        /*
        |--------------------------------------------------------------------------
        | Folder Filter
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | If folderId is empty:
        |     DO NOT use "'root' in parents"
        |
        | This makes Root Directory show ALL files
        | from the entire Google Drive.
        |
        */

        if ($folderId) {

            $escapedFolderId = addcslashes(
                $folderId,
                "\\'"
            );

            $query[] =
                "'{$escapedFolderId}' in parents";
        }

        /*
        |--------------------------------------------------------------------------
        | Exclude Folders
        |--------------------------------------------------------------------------
        |
        | Only actual files should appear in the file table.
        |
        */

        $query[] =
            "mimeType != 'application/vnd.google-apps.folder'";

        /*
        |--------------------------------------------------------------------------
        | Search By Name
        |--------------------------------------------------------------------------
        */

        if ($search) {

            $escapedSearch = addcslashes(
                $search,
                "\\'"
            );

            $query[] =
                "name contains '{$escapedSearch}'";
        }

        /*
        |--------------------------------------------------------------------------
        | From Date
        |--------------------------------------------------------------------------
        */

        if ($from) {

            $query[] =
                "modifiedTime >= '{$from}T00:00:00Z'";
        }

        /*
        |--------------------------------------------------------------------------
        | To Date
        |--------------------------------------------------------------------------
        */

        if ($to) {

            $query[] =
                "modifiedTime <= '{$to}T23:59:59Z'";
        }

        /*
        |--------------------------------------------------------------------------
        | File Type Filter
        |--------------------------------------------------------------------------
        */

        $mimeTypes = [

            'pdf' => [
                'application/pdf',
            ],

            'word' => [
                'application/msword',

                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],

            'excel' => [
                'application/vnd.ms-excel',

                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],

            'powerpoint' => [
                'application/vnd.ms-powerpoint',

                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ],

            'image' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
            ],

            'text' => [
                'text/plain',
            ],

            'csv' => [
                'text/csv',
                'application/csv',
            ],

            'zip' => [
                'application/zip',
                'application/x-zip-compressed',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Apply Type Filter
        |--------------------------------------------------------------------------
        */

        if (
            $type &&
            isset($mimeTypes[$type])
        ) {

            $types =
                $mimeTypes[$type];

            $typeQueries =
                collect($types)
                    ->map(function ($mimeType) {

                        return "mimeType = '{$mimeType}'";

                    })
                    ->implode(' or ');

            $query[] =
                '(' . $typeQueries . ')';
        }

        /*
        |--------------------------------------------------------------------------
        | Final Query
        |--------------------------------------------------------------------------
        */

        $finalQuery =
            implode(
                ' and ',
                $query
            );

        /*
        |--------------------------------------------------------------------------
        | Google Drive API Parameters
        |--------------------------------------------------------------------------
        */

        $params = [

            'q' => $finalQuery,

            'pageSize' => $pageSize,

            'fields' =>
                'nextPageToken,files(' .
                'id,' .
                'name,' .
                'mimeType,' .
                'size,' .
                'createdTime,' .
                'modifiedTime,' .
                'parents,' .
                'capabilities' .
                ')',

            'orderBy' =>
                'modifiedTime desc',
        ];

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        if ($pageToken) {

            $params['pageToken'] =
                $pageToken;
        }

        /*
        |--------------------------------------------------------------------------
        | API Request
        |--------------------------------------------------------------------------
        */

        return $this->drive
            ->files
            ->listFiles(
                $params
            );
    }

    /*
    |--------------------------------------------------------------------------
    | LIST FOLDERS
    |--------------------------------------------------------------------------
    */

    public function listFolders()
    {
        $response =
            $this->drive
                ->files
                ->listFiles([

                    'q' =>
                        "mimeType = 'application/vnd.google-apps.folder' and trashed = false",

                    'pageSize' => 1000,

                    'fields' =>
                        'files(id,name)',

                    'orderBy' =>
                        'name',
                ]);

        return $response->getFiles();
    }

    /*
    |--------------------------------------------------------------------------
    | GET FILE
    |--------------------------------------------------------------------------
    */

    public function getFile(
        string $fileId
    ) {
        return $this->drive
            ->files
            ->get(
                $fileId,
                [
                    'fields' =>
                        'id,name,mimeType,size,createdTime,modifiedTime,parents,capabilities',
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD FILE
    |--------------------------------------------------------------------------
    */

    public function downloadFile(
        string $fileId
    ) {
        return $this->drive
            ->files
            ->get(
                $fileId,
                [
                    'alt' => 'media',
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD FILE
    |--------------------------------------------------------------------------
    */

    public function uploadFile(
        string $filePath,
        string $fileName,
        string $mimeType,
        ?string $folderId = null
    ) {
        $metadata = new DriveFile;

        $metadata->setName(
            $fileName
        );

        /*
        |--------------------------------------------------------------------------
        | Destination Folder
        |--------------------------------------------------------------------------
        */

        if ($folderId) {

            $metadata->setParents([
                $folderId,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Upload
        |--------------------------------------------------------------------------
        */

        $content =
            file_get_contents(
                $filePath
            );

        if ($content === false) {

            throw new RuntimeException(
                'Unable to read uploaded file.'
            );
        }

        return $this->drive
            ->files
            ->create(
                $metadata,
                [
                    'data' => $content,

                    'mimeType' => $mimeType,

                    'uploadType' => 'multipart',

                    'fields' =>
                        'id,name,mimeType,size,createdTime,modifiedTime,parents,capabilities',
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE FILE
    |--------------------------------------------------------------------------
    */

    public function deleteFile(
        string $fileId
    ): void {

        $this->drive
            ->files
            ->delete(
                $fileId
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD DATA
    |--------------------------------------------------------------------------
    */

    public function getDashboardData(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get All Non-Trashed Items
        |--------------------------------------------------------------------------
        */

        $allFiles = [];

        $pageToken = null;

        do {

            $params = [

                'q' =>
                    'trashed = false',

                'pageSize' =>
                    1000,

                'fields' =>
                    'nextPageToken,files(' .
                    'id,' .
                    'name,' .
                    'mimeType,' .
                    'size,' .
                    'createdTime,' .
                    'modifiedTime,' .
                    'capabilities' .
                    ')',

                'orderBy' =>
                    'modifiedTime desc',
            ];

            if ($pageToken) {

                $params['pageToken'] =
                    $pageToken;
            }

            $response =
                $this->drive
                    ->files
                    ->listFiles(
                        $params
                    );

            foreach (
                $response->getFiles()
                as $file
            ) {

                $allFiles[] =
                    $file;
            }

            $pageToken =
                $response
                    ->getNextPageToken();

        } while ($pageToken);

        /*
        |--------------------------------------------------------------------------
        | Separate Files And Folders
        |--------------------------------------------------------------------------
        */

        $folders = [];

        $files = [];

        foreach (
            $allFiles as $file
        ) {

            if (
                $file->getMimeType() ===
                'application/vnd.google-apps.folder'
            ) {

                $folders[] =
                    $file;

            } else {

                $files[] =
                    $file;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Total Counts
        |--------------------------------------------------------------------------
        */

        $totalFiles =
            count($files);

        $totalFolders =
            count($folders);

        /*
        |--------------------------------------------------------------------------
        | Recent Files
        |--------------------------------------------------------------------------
        */

        $recentFiles =
            collect($files)
                ->sortByDesc(
                    function ($file) {

                        return $file
                            ->getModifiedTime();
                    }
                )
                ->take(10)
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Recent Uploads
        |--------------------------------------------------------------------------
        */

        $sevenDaysAgo =
            now()->subDays(7);

        $recentUploads =
            collect($files)
                ->filter(
                    function ($file) use (
                        $sevenDaysAgo
                    ) {

                        if (
                            ! $file->getCreatedTime()
                        ) {
                            return false;
                        }

                        return
                            Carbon::parse(
                                $file->getCreatedTime()
                            )->greaterThanOrEqualTo(
                                $sevenDaysAgo
                            );
                    }
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | File Type Statistics
        |--------------------------------------------------------------------------
        */

        $fileTypeCounts = [];

        foreach (
            $files as $file
        ) {

            $type =
                $this->getDashboardFileType(
                    $file->getMimeType(),
                    $file->getName()
                );

            if (
                ! isset(
                    $fileTypeCounts[$type]
                )
            ) {

                $fileTypeCounts[$type] =
                    0;
            }

            $fileTypeCounts[$type]++;
        }

        /*
        |--------------------------------------------------------------------------
        | Sort File Types
        |--------------------------------------------------------------------------
        */

        arsort(
            $fileTypeCounts
        );

        /*
        |--------------------------------------------------------------------------
        | Limit Categories
        |--------------------------------------------------------------------------
        */

        $fileTypes =
            collect(
                $fileTypeCounts
            )
                ->take(8)
                ->map(
                    function (
                        $count,
                        $type
                    ) {

                        return [

                            'type' =>
                                strtoupper(
                                    $type
                                ),

                            'count' =>
                                $count,
                        ];
                    }
                )
                ->values()
                ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Storage
        |--------------------------------------------------------------------------
        */

        $storage =
            $this->getStorageQuota();

        /*
        |--------------------------------------------------------------------------
        | Format Recent Files
        |--------------------------------------------------------------------------
        */

        $recentFilesData =
            $recentFiles
                ->map(
                    function ($file) {

                        return [

                            'id' =>
                                $file->getId(),

                            'name' =>
                                $file->getName(),

                            'mimeType' =>
                                $file->getMimeType(),

                            'size' =>
                                $file->getSize(),

                            'createdTime' =>
                                $file->getCreatedTime(),

                            'modifiedTime' =>
                                $file->getModifiedTime(),

                            'canDownload' =>
                                $file
                                    ->getCapabilities()
                                    ?->getCanDownload(),

                            'canDelete' =>
                                $file
                                    ->getCapabilities()
                                    ?->getCanDelete(),
                        ];
                    }
                )
                ->values()
                ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [

            'stats' => [

                'totalFiles' =>
                    $totalFiles,

                'totalFolders' =>
                    $totalFolders,

                'recentUploads' =>
                    $recentUploads,

                'storage' =>
                    $storage,

                'fileTypes' =>
                    $fileTypes,
            ],

            'recentFiles' =>
                $recentFilesData,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE DRIVE STORAGE QUOTA
    |--------------------------------------------------------------------------
    */

    public function getStorageQuota(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get Google Drive About Information
        |--------------------------------------------------------------------------
        */

        $about =
            $this->drive
                ->about
                ->get([
                    'fields' =>
                        'storageQuota',
                ]);

        $quota =
            $about->getStorageQuota();

        /*
        |--------------------------------------------------------------------------
        | Used
        |--------------------------------------------------------------------------
        */

        $used =
            (int) (
                $quota?->getUsage()
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Limit
        |--------------------------------------------------------------------------
        */

        $limitValue =
            $quota?->getLimit();

        $limit =
            $limitValue
                ? (int) $limitValue
                : null;

        /*
        |--------------------------------------------------------------------------
        | Percentage
        |--------------------------------------------------------------------------
        */

        if (
            $limit &&
            $limit > 0
        ) {

            $percentage =
                round(
                    ($used / $limit) * 100,
                    1
                );

        } else {

            $percentage = 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [

            'used' =>
                $used,

            'limit' =>
                $limit,

            'usedFormatted' =>
                $this->formatBytes(
                    $used
                ),

            'limitFormatted' =>
                $limit
                    ? $this->formatBytes(
                        $limit
                    )
                    : 'Unlimited',

            'percentage' =>
                $percentage,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT BYTES
    |--------------------------------------------------------------------------
    */

    protected function formatBytes(
        int $bytes
    ): string {

        if (
            $bytes <= 0
        ) {

            return '0 B';
        }

        $units = [

            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power =
            floor(
                log(
                    $bytes,
                    1024
                )
            );

        $power =
            min(
                $power,
                count($units) - 1
            );

        return
            round(
                $bytes /
                pow(
                    1024,
                    $power
                ),
                1
            ) .
            ' ' .
            $units[$power];
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD FILE TYPE
    |--------------------------------------------------------------------------
    */

    protected function getDashboardFileType(
        string $mimeType,
        string $fileName
    ): string {

        $mimeType =
            strtolower(
                $mimeType
            );

        $extension =
            strtolower(
                pathinfo(
                    $fileName,
                    PATHINFO_EXTENSION
                )
            );

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if (
            $mimeType ===
            'application/pdf'
            ||
            $extension === 'pdf'
        ) {

            return 'pdf';
        }

        /*
        |--------------------------------------------------------------------------
        | Word
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $extension,
                [
                    'doc',
                    'docx',
                ],
                true
            )
            ||
            str_contains(
                $mimeType,
                'word'
            )
            ||
            str_contains(
                $mimeType,
                'document'
            )
        ) {

            return 'word';
        }

        /*
        |--------------------------------------------------------------------------
        | Excel
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $extension,
                [
                    'xls',
                    'xlsx',
                ],
                true
            )
            ||
            str_contains(
                $mimeType,
                'excel'
            )
            ||
            str_contains(
                $mimeType,
                'spreadsheet'
            )
        ) {

            return 'excel';
        }

        /*
        |--------------------------------------------------------------------------
        | PowerPoint
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $extension,
                [
                    'ppt',
                    'pptx',
                ],
                true
            )
            ||
            str_contains(
                $mimeType,
                'powerpoint'
            )
            ||
            str_contains(
                $mimeType,
                'presentation'
            )
        ) {

            return 'powerpoint';
        }

        /*
        |--------------------------------------------------------------------------
        | Images
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $mimeType,
                'image/'
            )
            ||
            in_array(
                $extension,
                [
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'webp',
                    'svg',
                    'bmp',
                ],
                true
            )
        ) {

            return 'image';
        }

        /*
        |--------------------------------------------------------------------------
        | CSV
        |--------------------------------------------------------------------------
        */

        if (
            $extension === 'csv'
            ||
            $mimeType === 'text/csv'
            ||
            $mimeType === 'application/csv'
        ) {

            return 'csv';
        }

        /*
        |--------------------------------------------------------------------------
        | Text
        |--------------------------------------------------------------------------
        */

        if (
            $extension === 'txt'
            ||
            $mimeType === 'text/plain'
        ) {

            return 'text';
        }

        /*
        |--------------------------------------------------------------------------
        | ZIP
        |--------------------------------------------------------------------------
        */

        if (
            $extension === 'zip'
            ||
            $mimeType === 'application/zip'
        ) {

            return 'zip';
        }

        /*
        |--------------------------------------------------------------------------
        | Other
        |--------------------------------------------------------------------------
        */

        return 'other';
    }
}
