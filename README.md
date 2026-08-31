# File Management System

A web-based File Management System built with Laravel and Vue.js.

The application allows authenticated users to connect their Google account through Google OAuth and manage files from their Google Drive.

## Features

- Google OAuth authentication
- Google Drive integration
- Browse Google Drive files and folders
- Upload files to Google Drive
- Download files from Google Drive
- Delete files from Google Drive
- Search files by name
- Filter files by date and folder
- Secure OAuth token storage
- Automatic Google access-token refresh

## Tech Stack

- Laravel 12
- PHP 8.2
- Vue.js
- MySQL
- Laravel Socialite
- Google Drive API
- Google OAuth 2.0

## Installation

```bash
git clone https://github.com/YOUR_USERNAME/file-management-system.git
cd file-management-system

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure your database and Google OAuth credentials in the `.env` file.

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

Then run:

```bash
php artisan migrate
npm run build
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Security Notes

- Never commit `.env` files to GitHub.
- Never expose Google OAuth client secrets in frontend code.
- Store OAuth access tokens and refresh tokens securely.
- Use Google OAuth authorization instead of collecting user Google passwords.

## License

This project is licensed under the MIT License.
