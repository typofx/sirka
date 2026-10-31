<?php
/**
 * GitMe Control Panel - Overview Dashboard
 * Displays workspace overview, live doc site preview, and site metadata.
 */

$path = __DIR__;
while ($path !== dirname($path) && !file_exists($path . '/%/is_logged.php')) {
    $path = dirname($path);
}
if (file_exists($path . '/%/is_logged.php')) {
    require_once $path . '/%/is_logged.php';
}

$folder = basename(__DIR__);

/**
 * Preview Configuration
 * Set the relative or absolute path / URL to the documentation snapshot image here.
 */
$previewSnapshotImage = './assets/images/preview.png';

/**
 * Documentation (Litepaper) URL Configuration
 * Dynamically resolves the full URL and display path for the documentation site.
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
$litepaperFullUrl = $protocol . $host . $basePath . '/gitme.litepaper.php';
$litepaperDisplayUrl = $host . $basePath . '/gitme.litepaper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overview - $PLT Token for ACTM (Litepaper) - GitBook</title>
  <link rel="stylesheet" href="./gitme.styles.css?v=<?php echo time(); ?>">
  <script src="./gitme.panel.js" defer></script>
</head>
<body class="gb-panel-body" data-folder="<?php echo htmlspecialchars($folder); ?>">
  <div class="gb-panel-app">
    <!-- Admin Sidebar -->
    <aside class="gb-sidebar" id="gbSidebar">
      <!-- Top Bar: Logo & Actions -->
      <div class="gb-sidebar-top-bar">
        <div class="gb-workspace-badge" title="GitBook Workspace">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 3h5v5"></path>
            <path d="M8 21H3v-5"></path>
            <path d="M21 3l-7.5 7.5"></path>
            <path d="M3 21l7.5-7.5"></path>
          </svg>
        </div>
        <div class="gb-top-actions">
          <button class="gb-icon-btn" title="Home" type="button" data-toast="Home">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </button>
          <button class="gb-icon-btn" title="Search" type="button" data-toast="Search documentation">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>
          <button class="gb-icon-btn" title="Notifications" type="button" data-toast="Notifications">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Active Site Selector Card -->
      <div class="gb-site-selector-card">
        <div class="gb-site-selector-header" id="gbSiteDropdownTrigger" title="Select Site">
          <div class="gb-site-selector-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span>$PLT Token for ACTM (...</span>
          </div>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <div class="gb-site-selector-url"><?php echo htmlspecialchars($litepaperFullUrl); ?></div>
        <div class="gb-site-selector-actions">
          <button class="gb-site-sub-btn" id="btnPreviewReload" type="button" title="Refresh Live Preview">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            <span>Preview</span>
          </button>
          <a class="gb-site-sub-btn" href="./gitme.litepaper.php" target="_blank" rel="noopener noreferrer" title="Open site in new tab">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              <polyline points="15 3 21 3 21 9"></polyline>
              <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            <span>Visit</span>
          </a>
        </div>
      </div>

      <!-- Git Sync Row -->
      <div class="gb-gitsync-row">
        <div class="gb-gitsync-label">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
          </svg>
          <span>Git Sync</span>
        </div>
        <a class="gb-gitsync-link" href="#" data-toast="Git Sync setup opened">Set up</a>
      </div>

      <!-- Navigation List -->
      <ul class="gb-nav-list">
        <li class="gb-nav-item active">
          <a href="./index.php">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
              <span>Overview</span>
            </span>
          </a>
        </li>
        <li class="gb-nav-item">
          <a href="#" data-toast="Change requests view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="18" cy="18" r="3"></circle>
                <circle cx="6" cy="6" r="3"></circle>
                <path d="M13 6h3a2 2 0 0 1 2 2v7"></path>
                <line x1="6" y1="9" x2="6" y2="21"></line>
              </svg>
              <span>Change requests</span>
            </span>
          </a>
        </li>
        <li class="gb-nav-item">
          <a href="#" data-toast="Site structure view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="5" r="3"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <circle cx="6" cy="19" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <path d="M6 16v-2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"></path>
              </svg>
              <span>Site structure</span>
            </span>
          </a>
        </li>
        <li class="gb-nav-item">
          <a href="#" data-toast="Customize settings view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3z"></path>
                <path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"></path>
                <path d="M14.5 17.5L4.5 7.5"></path>
              </svg>
              <span>Customize</span>
            </span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </a>
        </li>
        <li class="gb-nav-item">
          <a href="#" data-toast="Extend integrations view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4"></path>
                <path d="M12 18v4"></path>
                <path d="M4.93 4.93l2.83 2.83"></path>
                <path d="M16.24 16.24l2.83 2.83"></path>
                <path d="M2 12h4"></path>
                <path d="M18 12h4"></path>
                <path d="M4.93 19.07l2.83-2.83"></path>
                <path d="M16.24 7.76l2.83-2.83"></path>
              </svg>
              <span>Extend</span>
            </span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </a>
        </li>
        <li class="gb-nav-item">
          <a href="#" data-toast="Site settings view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
              </svg>
              <span>Settings</span>
            </span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </a>
        </li>
      </ul>

      <!-- Analyze Section -->
      <div class="gb-nav-section-title">Analyze</div>
      <ul class="gb-nav-list">
        <li class="gb-nav-item">
          <a href="#" data-toast="Traffic analytics view is planned for next phase">
            <span class="gb-nav-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
              </svg>
              <span>Traffic</span>
            </span>
          </a>
        </li>
      </ul>

      <!-- Footer Bar -->
      <div class="gb-sidebar-footer">
        <div class="gb-footer-icons">
          <button class="gb-icon-btn" title="Help & Support" type="button" data-toast="Help modal opened">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </button>
          <button class="gb-icon-btn" title="Feedback" type="button" data-toast="Feedback form opened">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
              <line x1="9" y1="9" x2="9.01" y2="9"></line>
              <line x1="15" y1="9" x2="15.01" y2="9"></line>
            </svg>
          </button>
        </div>
        <button class="gb-icon-btn" id="btnToggleSidebar" title="Toggle Sidebar" type="button">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="9" y1="3" x2="9" y2="21"></line>
          </svg>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="gb-main-content">
      <!-- Main Header -->
      <header class="gb-main-header">
        <button class="gb-mobile-menu-btn" id="btnMobileMenu" title="Toggle Navigation" type="button" aria-label="Toggle Navigation">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div class="gb-title-group">
          <h1 class="gb-main-title">$PLT Token for ACTM (Litepaper)</h1>
        </div>
        <div class="gb-header-actions">
          <button class="gb-btn-outline" type="button" data-toast="Manage members modal opened">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <line x1="20" y1="8" x2="20" y2="14"></line>
              <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <span>Manage members</span>
          </button>
          <a class="gb-btn-white" href="./gitme.litepaper.php" target="_blank" rel="noopener noreferrer">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              <polyline points="15 3 21 3 21 9"></polyline>
              <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            <span>Visit</span>
          </a>
        </div>
      </header>

      <!-- Center Two-Column Grid -->
      <div class="gb-overview-grid">
        <!-- Left: Live Preview Window -->
        <div class="gb-preview-card">
          <div class="gb-preview-browser-bar">
            <div class="gb-preview-browser-left">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
              </svg>
              <span>$PLT Token for ACTM (Litepaper)</span>
            </div>
            <div class="gb-preview-browser-right">
              <span>Interactive Site Preview</span>
              <a href="./gitme.litepaper.php" target="_blank" rel="noopener noreferrer" class="gb-icon-btn" title="Open site in new tab">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                  <polyline points="15 3 21 3 21 9"></polyline>
                  <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
              </a>
            </div>
          </div>
          <div class="gb-preview-snapshot-container">
            <img src="<?php echo htmlspecialchars($previewSnapshotImage); ?>" 
                 alt="$PLT Token for ACTM Documentation Preview" 
                 class="gb-preview-snapshot-img" 
                 loading="lazy">
            <div class="gb-preview-snapshot-overlay">
              <a href="./gitme.litepaper.php" target="_blank" rel="noopener noreferrer" class="gb-btn-preview-visit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                  <polyline points="15 3 21 3 21 9"></polyline>
                  <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                <span>Open Live Site</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Right: Metadata Summary Card -->
        <div class="gb-meta-card">
          <div class="gb-meta-row">
            <span class="gb-meta-label">Status</span>
            <span class="gb-meta-status-published">Published</span>
          </div>
          <div class="gb-meta-row">
            <span class="gb-meta-label">URL</span>
            <span class="gb-meta-value">
              <a class="gb-meta-url" id="linkSiteUrl" href="./gitme.litepaper.php" target="_blank" rel="noopener noreferrer" title="Click to copy link" data-copy-url="<?php echo htmlspecialchars($litepaperFullUrl); ?>"><?php echo htmlspecialchars($litepaperDisplayUrl); ?></a>
            </span>
          </div>
          <div class="gb-meta-row">
            <span class="gb-meta-label">Audience</span>
            <span class="gb-meta-value">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
              </svg>
              <span>Public</span>
            </span>
          </div>
          <div class="gb-meta-row">
            <span class="gb-meta-label">Git Sync</span>
            <span class="gb-meta-value gb-meta-value-muted">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="18" cy="18" r="3"></circle>
                <circle cx="6" cy="6" r="3"></circle>
                <path d="M6 9v12"></path>
                <path d="M18 9a9 9 0 0 0-9 9"></path>
              </svg>
              <span>Not connected</span>
            </span>
          </div>
          <div class="gb-meta-row-footer">
            <div class="gb-members-group">
              <span>Members</span>
              <span class="gb-badge-counter">3</span>
              <span class="gb-member-avatar" title="Admin Avatar">P</span>
              <span>1 admin</span>
            </div>
            <a class="gb-connect-mcp" href="#" data-toast="Connect MCP modal opened">Connect MCP</a>
          </div>
        </div>
      </div>

      <!-- Bottom Add Content Action -->
      <div class="gb-bottom-bar">
        <button class="gb-add-content-btn" type="button" data-toast="Add content options opened">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>Add content</span>
        </button>
      </div>
    </main>
  </div>

  <!-- Toast Notification Container -->
  <div class="gb-toast" id="gbToast" role="status" aria-live="polite">Notification</div>
</body>
</html>
