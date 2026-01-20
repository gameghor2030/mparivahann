// Initialize default APK configuration for GitHub-friendly system
document.addEventListener('DOMContentLoaded', function() {
    // Check if we already have config in localStorage
    if (!localStorage.getItem('apk_config')) {
        // Set default configuration
        const defaultConfig = {
            "latest_version": "1.0.0",
            "latest_version_code": 1,
            "file_path": "mParivahan.apk",
            "file_size": 2621440, // 2.5MB in bytes
            "download_url": "./apk_files/mParivahan.apk",
            "last_updated": new Date().toISOString(),
            "release_notes": "Initial release of the application."
        };
        
        localStorage.setItem('apk_config', JSON.stringify(defaultConfig));
        console.log('Default APK configuration initialized');
    }
    
    // Check if we have APKs in localStorage
    if (!localStorage.getItem('apks')) {
        localStorage.setItem('apks', JSON.stringify([]));
        console.log('Empty APKs list initialized');
    }
});