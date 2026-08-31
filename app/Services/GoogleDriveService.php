<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use RuntimeException;

class GoogleDriveService
{
    protected User $user;

    protected Client $client;

    protected Drive $drive;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->client = new Client;

        $this->client->setClientId(
            config('services.google.client_id')
        );

        $this->client->setClientSecret(
            config('services.google.client_secret')
        );

        $this->client->setAccessToken([
            'access_token' => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at
                ? max(0, now()->diffInSeconds(
                    $user->google_token_expires_at,
                    false
                ))
                : 0,
        ]);

        $this->refreshTokenIfNeeded();

        $this->drive = new Drive($this->client);
    }

    protected function refreshTokenIfNeeded(): void
    {
        if (! $this->client->isAccessTokenExpired()) {
            return;
        }

        if (! $this->user->google_refresh_token) {
            throw new RuntimeException(
                'Google refresh token is missing. Please login again.'
            );
        }

        $newToken = $this->client->fetchAccessTokenWithRefreshToken(
            $this->user->google_refresh_token
        );

        if (isset($newToken['error'])) {
            throw new RuntimeException(
                $newToken['error_description']
                    ?? 'Unable to refresh Google access token.'
            );
        }

        $this->user->update([
            'google_token' => $newToken['access_token'],
            'google_token_expires_at' => now()->addSeconds(
                $newToken['expires_in'] ?? 3600
            ),
        ]);
    }

    public function listFiles(
        ?string $from = null,
        ?string $to = null,
        ?string $search = null,
        ?string $folderId = null
    ) {
        $conditions = [
            'trashed = false',
        ];

        if ($from) {
            $conditions[] = "modifiedTime >= '{$from}T00:00:00'";
        }

        if ($to) {
            $conditions[] = "modifiedTime <= '{$to}T23:59:59'";
        }

        if ($search) {
            $escaped = str_replace("'", "\\'", $search);

            $conditions[] = "name contains '{$escaped}'";
        }

        if ($folderId) {
            $conditions[] = "'{$folderId}' in parents";
        }

        $response = $this->drive->files->listFiles([
            'q' => implode(' and ', $conditions),
            'pageSize' => 100,
            'orderBy' => 'modifiedTime desc',
            'fields' => 'files(id,name,mimeType,size,createdTime,modifiedTime,parents,webViewLink,capabilities)',
        ]);

        return $response->getFiles();
    }

    public function getFile(string $fileId)
    {
        return $this->drive->files->get(
            $fileId,
            [
                'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents,capabilities',
            ]
        );
    }

    public function downloadFile(string $fileId)
    {
        return $this->drive->files->get(
            $fileId,
            [
                'alt' => 'media',
            ]
        );
    }

    public function uploadFile(
        string $localPath,
        string $fileName,
        string $mimeType,
        ?string $folderId = null
    ) {
        $metadata = new DriveFile([
            'name' => $fileName,
        ]);

        if ($folderId) {
            $metadata->setParents([$folderId]);
        }

        $content = file_get_contents($localPath);

        return $this->drive->files->create(
            $metadata,
            [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents',
            ]
        );
    }

    public function listFolders()
    {
        return $this->drive->files->listFiles([
            'q' => "mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'pageSize' => 100,
            'orderBy' => 'name',
            'fields' => 'files(id,name,parents)',
        ])->getFiles();
    }

    public function deleteFile(string $fileId): void
    {
        $this->drive->files->delete($fileId);
    }
}
