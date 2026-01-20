# GitHub-Friendly APK Download Site

This is a lightweight, GitHub-friendly application download site that operates entirely on the frontend with no server-side dependencies.

## Features

- **Pure HTML/CSS/JS**: No server-side code required
- **GitHub Pages Compatible**: Works perfectly with GitHub Pages hosting
- **Client-Side Storage**: Uses browser localStorage for configuration
- **Admin Panel**: Secure admin interface with login credentials
- **APK Management**: Upload and manage APK files through the admin panel
- **Responsive Design**: Works on all device sizes

## Admin Panel

- **Login URL**: `/admin/admin-login.html`
- **Default Credentials**:
  - Admin ID: `admin`
  - Password: `admin1234`

## How It Works

1. The system stores APK configuration in browser localStorage
2. Admin panel allows uploading APK metadata (file paths are stored, not actual files)
3. Frontend retrieves configuration from localStorage to display current APK info
4. Downloads link to predefined file paths (you'll need to host APK files separately)

## GitHub Integration

To make this work fully with GitHub repositories:

1. Host APK files in a publicly accessible location (GitHub releases, CDN, etc.)
2. Update the admin panel to point to these public URLs
3. The admin panel can be enhanced to work directly with GitHub API to update configuration files

## Security Note

⚠️ **Important**: This system stores credentials in localStorage which is not secure for production use. For production deployments, implement proper authentication with server-side validation.

## Setup

1. Clone or download this repository
2. Serve via GitHub Pages or any static hosting service
3. Access the admin panel to configure APK downloads
4. Use the default credentials (admin/admin1234) to log in initially