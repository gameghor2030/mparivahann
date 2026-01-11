// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// Navbar scroll effect
let lastScroll = 0;
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset;

  if (currentScroll > 100) {
    navbar.style.background = 'rgba(255, 255, 255, 0.95)';
    navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
  } else {
    navbar.style.background = 'rgba(255, 255, 255, 0.8)';
    navbar.style.boxShadow = 'none';
  }

  lastScroll = currentScroll;
});

// Intersection Observer for fade-in animations
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -80px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
      // Unobserve after animation to prevent re-triggering
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

// Observe feature cards and screenshot cards
document.querySelectorAll('.feature-card, .screenshot-card').forEach(card => {
  observer.observe(card);
});

// Add touch feedback for screenshot cards
document.querySelectorAll('.screenshot-card').forEach(card => {
  card.addEventListener('touchstart', function () {
    this.style.transform = 'scale(1.02) translateY(-5px)';
  });

  card.addEventListener('touchend', function () {
    this.style.transform = '';
  });
});

// Parallax effect for hero section
window.addEventListener('scroll', () => {
  const scrolled = window.pageYOffset;
  const hero = document.querySelector('.hero');
  const orbs = document.querySelectorAll('.gradient-orb');

  if (hero) {
    orbs.forEach((orb, index) => {
      const speed = 0.5 + (index * 0.1);
      orb.style.transform = `translateY(${scrolled * speed}px)`;
    });
  }
});

// Add hover effect to download buttons
document.querySelectorAll('.download-btn').forEach(btn => {
  btn.addEventListener('mouseenter', function () {
    this.style.transform = 'translateY(-3px) scale(1.02)';
  });

  btn.addEventListener('mouseleave', function () {
    this.style.transform = 'translateY(0) scale(1)';
  });
});

// Animate numbers/counters (if you add them later)
function animateValue(element, start, end, duration) {
  let startTimestamp = null;
  const step = (timestamp) => {
    if (!startTimestamp) startTimestamp = timestamp;
    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
    element.textContent = Math.floor(progress * (end - start) + start);
    if (progress < 1) {
      window.requestAnimationFrame(step);
    }
  };
  window.requestAnimationFrame(step);
}

// Add ripple effect to buttons (moved to DOMContentLoaded to ensure buttons exist)
// This will be initialized in DOMContentLoaded event

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .download-btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Lazy load images (if you add real images later)
if ('IntersectionObserver' in window) {
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          imageObserver.unobserve(img);
        }
      }
    });
  });

  document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}

console.log('🚀 App landing page loaded successfully!');

// ============================================
// PHP-BASED APK DOWNLOAD SYSTEM
// ============================================

let apkInfo = null;

// Initialize - Fetch APK info from PHP API on page load
document.addEventListener('DOMContentLoaded', function () {
  // Fetch latest APK information
  fetchApkInfo();
  
  // Auto-refresh every 30 seconds to check for updates
  setInterval(fetchApkInfo, 30000);

  // Add ripple effect to download buttons
  document.querySelectorAll('.download-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      ripple.classList.add('ripple');

      this.appendChild(ripple);

      setTimeout(() => {
        if (ripple.parentNode) {
          ripple.remove();
        }
      }, 600);
    });
  });

  // Connect download button to the download function
  const downloadBtn = document.getElementById('downloadBtn');
  if (downloadBtn) {
    console.log('✅ Download button found, attaching handler');
    downloadBtn.addEventListener('click', downloadAPK);
  } else {
    console.error('❌ Download button not found! ID: downloadBtn');
  }
});

// Fetch APK information from PHP API
async function fetchApkInfo() {
  try {
    const apiUrl = './api/apk_info.php';
    console.log('📡 Fetching APK info from PHP API...');
    
    const response = await fetch(apiUrl);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('📡 API response:', data);

    if (data.status === 'success' && data.data) {
      apkInfo = data.data;
      updateDownloadButton();
      console.log('✅ APK info loaded:', apkInfo.filename, 'v' + apkInfo.version);
    } else {
      console.warn('⚠️ No APK available:', data.message);
      apkInfo = null;
    }
  } catch (error) {
    console.error('❌ Error fetching APK info:', error);
    console.error('Error details:', {
      message: error.message,
      stack: error.stack,
      name: error.name
    });
    // Don't clear apkInfo on error - keep the last known info
  }
}

// Update download button with latest APK info
function updateDownloadButton() {
  if (!apkInfo) return;

  // Update the download warning text to show file info
  const warnings = document.querySelectorAll('.download-warning');
  warnings.forEach(warning => {
    warning.innerHTML = `📥 Latest: <strong>${apkInfo.filename}</strong> (${apkInfo.size_formatted}) - v${apkInfo.version}`;
    warning.style.color = '#138808';
    warning.style.fontWeight = '600';
  });
}

// Download APK from PHP system
async function downloadAPK(event) {
  event.preventDefault();
  event.stopPropagation();

  console.log('🔽 Download button clicked');
  console.log('📦 APK info:', apkInfo);

  if (!apkInfo) {
    console.warn('⚠️ No APK available - fetching info...');
    showNotification('Loading APK Info...', 'Please wait...', 'loading');
    
    // Try to fetch APK info one more time
    await fetchApkInfo();
    
    if (!apkInfo) {
      showNotification('No APK Available', 'Please contact administrator to upload APK files', 'error');
      return;
    }
  }

  try {
    showNotification('Preparing Download...', 'Please wait...', 'loading');
    
    // Get the filename for the download
    const filename = apkInfo.filename || 'mParivahan.apk';
    
    // Use PHP download endpoint with filename parameter for better mobile support
    const downloadUrl = './apk_download.php?download=true&file=' + encodeURIComponent(filename);
    console.log('🔗 Download URL:', downloadUrl);
    console.log('📱 Filename:', filename);

    // For mobile browsers, use window.location or window.open for better compatibility
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
      // Mobile browsers work better with direct navigation
      console.log('📱 Mobile device detected, using direct download');
      window.location.href = downloadUrl;
    } else {
      // Desktop browsers can use the download attribute
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = filename;
      link.style.display = 'none';
      link.rel = 'noopener noreferrer';

      document.body.appendChild(link);
      
      // Trigger download
      try {
        link.click();
        console.log('✅ Download link clicked');
        
        // Clean up after a short delay
        setTimeout(() => {
          if (link.parentNode) {
            document.body.removeChild(link);
          }
        }, 1000);
      } catch (clickError) {
        console.warn('⚠️ Direct click failed, trying alternative method:', clickError);
        // Fallback: Open in new window if direct download fails
        window.location.href = downloadUrl;
      }
    }

    // Remove loading notification and show success
    const notifications = document.querySelectorAll('.download-notification');
    notifications.forEach(n => {
      if (n.classList.contains('loading')) {
        n.remove();
      }
    });

    showNotification('Download Started', `${apkInfo.filename || 'APK'} is downloading...`, 'success');
    console.log('✅ Download initiated successfully');
  } catch (error) {
    console.error('❌ Download error:', error);
    console.error('Error details:', {
      message: error.message,
      stack: error.stack,
      name: error.name
    });
    
    // Remove loading notification if exists
    const notifications = document.querySelectorAll('.download-notification');
    notifications.forEach(n => {
      if (n.classList.contains('loading')) {
        n.remove();
      }
    });
    
    showNotification('Download Failed', error.message || 'Could not download file. Please try again.', 'error');
  }
}

// Show notification
function showNotification(title, message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `download-notification ${type}`;

  const icons = {
    success: '<path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#138808"/><path d="M2 17L12 22L22 17V12L12 17L2 12V17Z" fill="#138808"/>',
    error: '<circle cx="12" cy="12" r="10" fill="#DC2626"/><path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="2"/>',
    loading: '<circle cx="12" cy="12" r="10" stroke="#1E40AF" stroke-width="2" fill="none"/>'
  };

  notification.innerHTML = `
        <div class="notification-content">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                ${icons[type] || icons.success}
            </svg>
            <div>
                <strong>${title}</strong>
                <p>${message}</p>
            </div>
        </div>
    `;

  document.body.appendChild(notification);

  setTimeout(() => notification.classList.add('show'), 100);

  if (type !== 'loading') {
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => document.body.removeChild(notification), 300);
    }, 4000);
  }
}

// ============================================
// FILE MANAGEMENT SYSTEM
// ============================================

// Local storage for files
let files = JSON.parse(localStorage.getItem('appFiles')) || [];

// Initialize file list on page load
document.addEventListener('DOMContentLoaded', function () {
  loadFiles();

  // SECRET: Press Ctrl+Shift+A to open admin panel
  document.addEventListener('keydown', function (e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'A') {
      e.preventDefault();
      toggleAdmin();
    }
  });
});

// Toggle Admin Panel (Hidden - use Ctrl+Shift+A)
function toggleAdmin() {
  const panel = document.getElementById('adminPanel');
  const password = prompt('Enter admin password:');

  if (password === 'admin123') { // Change this to a secure password
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  } else if (password !== null) {
    alert('Incorrect password!');
  }
}

// Add File to List
function addFile() {
  const fileName = document.getElementById('fileName').value;
  const fileVersion = document.getElementById('fileVersion').value;
  const downloadUrl = document.getElementById('downloadUrl').value;
  const fileSize = document.getElementById('fileSize').value;
  const fileDescription = document.getElementById('fileDescription').value;

  if (!fileName || !fileVersion || !downloadUrl) {
    showStatus('Please fill in File Name, Version, and Download URL', 'error');
    return;
  }

  showStatus('Adding file...', 'loading');

  try {
    // Add file to local list
    const newFile = {
      id: Date.now(),
      name: fileName,
      version: fileVersion,
      description: fileDescription || 'No description',
      downloadLink: downloadUrl,
      size: fileSize || 'N/A',
      uploadDate: new Date().toISOString(),
      type: getFileType(fileName)
    };

    files.push(newFile);
    localStorage.setItem('appFiles', JSON.stringify(files));

    showStatus('File added successfully!', 'success');
    loadFiles();

    // Clear form
    document.getElementById('fileName').value = '';
    document.getElementById('fileVersion').value = '';
    document.getElementById('downloadUrl').value = '';
    document.getElementById('fileSize').value = '';
    document.getElementById('fileDescription').value = '';
  } catch (error) {
    showStatus('Failed to add file: ' + error.message, 'error');
    console.error('Error:', error);
  }
}

// Load and Display Files
function loadFiles() {
  const fileList = document.getElementById('fileList');

  if (files.length === 0) {
    fileList.innerHTML = '';
    return;
  }

  fileList.innerHTML = files.map(file => `
        <div class="file-card" data-id="${file.id}">
            <div class="file-icon">
                ${getFileIcon(file.type)}
            </div>
            <div class="file-info">
                <h3 class="file-name">${file.name}</h3>
                <div class="file-meta">
                    <span class="file-version">v${file.version}</span>
                    <span class="file-size">${file.size}</span>
                    <span class="file-date">${formatDate(file.uploadDate)}</span>
                </div>
                <p class="file-description">${file.description}</p>
            </div>
            <div class="file-actions">
                <a href="${file.downloadLink}" class="download-file-btn" download>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 15L7 10H17L12 15Z" fill="currentColor"/>
                        <path d="M12 3V14" stroke="currentColor" stroke-width="2"/>
                        <path d="M5 19H19" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Download
                </a>
                <button onclick="deleteFile(${file.id})" class="delete-file-btn" title="Delete">
                    🗑️
                </button>
            </div>
        </div>
    `).join('');
}

// Delete File
function deleteFile(fileId) {
  if (confirm('Are you sure you want to delete this file?')) {
    files = files.filter(f => f.id !== fileId);
    localStorage.setItem('appFiles', JSON.stringify(files));
    loadFiles();
    showStatus('File deleted', 'success');
  }
}

// Helper Functions
function showStatus(message, type) {
  const statusDiv = document.getElementById('uploadStatus');
  statusDiv.className = `upload-status ${type}`;
  statusDiv.textContent = message;
  statusDiv.style.display = 'block';

  if (type !== 'loading') {
    setTimeout(() => {
      statusDiv.style.display = 'none';
    }, 5000);
  }
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

function getFileType(filename) {
  const ext = filename.split('.').pop().toLowerCase();
  if (ext === 'apk') return 'apk';
  if (['zip', 'rar', '7z'].includes(ext)) return 'archive';
  if (['pdf', 'doc', 'docx'].includes(ext)) return 'document';
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'image';
  return 'file';
}

function getFileIcon(type) {
  const icons = {
    'apk': '📱',
    'archive': '📦',
    'document': '📄',
    'image': '🖼️',
    'file': '📁'
  };
  return icons[type] || '📁';
}

function formatDate(isoDate) {
  const date = new Date(isoDate);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

