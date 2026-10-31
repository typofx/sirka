<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/is_logged.php')) {
    $currentDir = dirname($currentDir);
}
if (file_exists($currentDir . '/%/is_logged.php')) {
    require_once $currentDir . '/%/is_logged.php';
}
?>
<?php
require_once __DIR__ . '/components/sidebar.php';
?> 
<?php
$folder = basename(__DIR__);
$siteTitle = '$PLT Token for ACTM (Litepaper)';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title><?php echo htmlspecialchars($siteTitle); ?></title>
    <meta name="description" content="for ACTM Automated Crypto Teller Machine Project" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars($folder); ?>.styles.css?v=<?php echo time(); ?>" />
</head>

<body data-folder="<?php echo htmlspecialchars($folder); ?>">
    <div class="gb-app">
        <!-- Top Navigation Bar -->
        <header class="gb-topbar" data-module="<?php echo htmlspecialchars($folder); ?>">
            <div class="gb-topbar-left">
                <button class="gb-menu-toggle" aria-label="Toggle navigation">
                    <svg viewBox="0 0 448 512" fill="currentColor" width="18" height="18">
                        <path d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM416 448L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/>
                    </svg>
                </button>
                <div class="gb-brand-icon" title="Print document">
                    <svg viewBox="0 0 512 512" fill="currentColor" width="16" height="16">
                        <path d="M128 0C92.7 0 64 28.7 64 64l0 96 64 0 0-96 256 0 0 96 64 0 0-96c0-35.3-28.7-64-64-64L128 0zM19.2 192C8.6 192 0 200.6 0 211.2L0 352c0 35.3 28.7 64 64 64l64 0 0 64c0 17.7 14.3 32 32 32l192 0c17.7 0 32-14.3 32-32l0-64 64 0c35.3 0 64-28.7 64-64l0-140.8c0-10.6-8.6-19.2-19.2-19.2L19.2 192zM384 448l-256 0 0-96 256 0 0 96zm48-176a24 24 0 1 1 0-48 24 24 0 1 1 0 48z"/>
                    </svg>
                </div>
                <div class="gb-topbar-title"><?php echo htmlspecialchars($siteTitle); ?></div>
            </div>
            <div class="gb-topbar-right">
                <a href="./index.php" class="gb-panel-link-btn" title="Back to Control Panel">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Panel</span>
                </a>
                <button class="gb-search-box" type="button" aria-label="Search documentation">
                    <div class="gb-search-left">
                        <svg viewBox="0 0 512 512" fill="currentColor" width="14" height="14" class="gb-search-icon">
                            <path d="M368 208a160 160 0 1 0 -320 0 160 160 0 1 0 320 0zM337.1 371.1C301.7 399.2 256.8 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208c0 48.8-16.8 93.7-44.9 129.1L505 471c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0L337.1 371.1z"/>
                        </svg>
                        <span class="gb-search-placeholder">Search…</span>
                    </div>
                    <div class="gb-search-shortcuts">
                        <kbd class="gb-kbd">Ctrl</kbd>
                        <kbd class="gb-kbd">K</kbd>
                    </div>
                </button>
            </div>
        </header>

        <div class="gb-container">
            <!-- Sidebar Navigation Component -->
            <?php echo $sidebarHtml; ?>

            <!-- Main Documentation Content -->
            <main class="gb-main">
                <article class="gb-article">
                    <!-- Top Cover Graphic (ACTM Concept) -->
                    <div class="gb-cover-container">
                        <img src="assets/images/PlataBackground.png" alt="ACTM Concept Project - Lodgements &amp; Withdraws" class="gb-cover-img" />
                    </div>

                    <!-- Heading Section -->
                    <header class="gb-page-header">
                        <div class="gb-title-row">
                            <h1 class="gb-page-title" id="plt-plata-token">$PLT Plata Token</h1>
                            <button class="gb-copy-btn" type="button" title="Copy page link">
                                <svg viewBox="0 0 448 512" fill="currentColor" width="13" height="13">
                                    <path d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l140.1 0L384 96.1 384 320c0 8.8-7.2 16-16 16zM384 32l-140.1 0c-17 0-33.3 6.7-45.3 18.7L154.7 94.7c-12 12-18.7 28.3-18.7 45.3L136 320c0 35.3 28.7 64 64 64l184 0c35.3 0 64-28.7 64-64l0-223.9c0-17-6.7-33.3-18.7-45.3L395.3 50.7c-12-12-28.3-18.7-45.3-18.7zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-32-48 0 0 32c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l32 0 0-48-32 0z"/>
                                </svg>
                                <span>Copy</span>
                                <svg viewBox="0 0 320 512" fill="currentColor" width="9" height="9">
                                    <path d="M137.4 374.6c12.5 12.5 32.8 12.5 45.3 0l128-128c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8L32 192c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l128 128z"/>
                                </svg>
                            </button>
                        </div>
                        <p class="gb-page-subtitle">for ACTM Automated Crypto Teller Machine Project</p>
                    </header>

                    <!-- Hero Polygon Graphic -->
                    <figure class="gb-figure">
                        <div class="gb-figure-img-wrapper">
                            <img src="assets/images/timthumb.jpg" alt="$PLT Plata Token is on Polygon Blockchain" class="gb-figure-img" />
                        </div>
                        <figcaption class="gb-figure-caption">$PLT Plata Token is on Polygon Blockchain.</figcaption>
                    </figure>

                    <!-- Information Details Section -->
                    <section class="gb-section">
                        <h2 class="gb-section-title">PLATA (PLT)</h2>
                        <div>
                            <a href="https://www.plata.ie/" target="_blank" rel="noopener noreferrer" class="gb-link">
                                <span>https://www.plata.ie/</span>
                                <svg viewBox="0 0 384 512" fill="currentColor" width="11" height="11">
                                    <path d="M328 96c13.3 0 24 10.7 24 24l0 240c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-182.1L57 425c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9L270.1 144 88 144c-13.3 0-24-10.7-24-24s10.7-24 24-24l240 0z"/>
                                </svg>
                            </a>
                        </div>
                        <p class="gb-section-desc">ACTM &amp; Plata (PLT) Token from Typo FX</p>
                    </section>

                    <!-- Bottom Pagination Navigation -->
                    <div class="gb-pagination">
                        <a href="#table-of-contents" class="gb-pagination-card gb-pagination-next">
                            <div class="gb-pagination-content">
                                <span class="gb-pagination-label">Next</span>
                                <span class="gb-pagination-title">Table of Contents</span>
                            </div>
                            <svg viewBox="0 0 320 512" fill="currentColor" width="12" height="12" class="gb-pagination-icon">
                                <path d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/>
                            </svg>
                        </a>
                    </div>
                </article>
            </main>
        </div>

        <!-- Search Modal Overlay -->
        <div class="gb-modal-overlay" role="dialog" aria-modal="true" aria-label="Search Documentation">
            <div class="gb-modal-content">
                <div class="gb-modal-header">
                    <svg viewBox="0 0 512 512" fill="currentColor" width="16" height="16" style="color: var(--text-muted);">
                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0s208 93.1 208 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                    </svg>
                    <input type="text" class="gb-modal-input" placeholder="Search documentation..." />
                    <kbd class="gb-kbd">ESC</kbd>
                </div>
                <div class="gb-modal-body">
                    No recent searches. Type a keyword to begin searching.
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo htmlspecialchars($folder); ?>.js?v=<?php echo time(); ?>" defer></script>
</body>
</html>
