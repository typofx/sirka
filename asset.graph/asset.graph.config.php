<?php
$config_path = __DIR__ . '/asset.graph.config.json';

// Load existing settings
$config = [];
if (file_exists($config_path)) {
    $config = json_decode(file_get_contents($config_path), true);
}

// Default values if JSON is empty
$font = isset($config['canvas']['font']) ? $config['canvas']['font'] : 'Montserrat';
$bg_color = isset($config['canvas']['background_color']) ? $config['canvas']['background_color'] : '#1e2730';
$grid_color = isset($config['canvas']['grid_color']) ? $config['canvas']['grid_color'] : '#4a667a';
$text_color = isset($config['canvas']['text_color']) ? $config['canvas']['text_color'] : '#cccccc';
$size = isset($config['canvas']['size']) ? $config['canvas']['size'] : 'medium';
$font_size = isset($config['canvas']['font_size']) ? $config['canvas']['font_size'] : '12';
$line_color = isset($config['symbol']['line_color']) ? $config['symbol']['line_color'] : 'pink';
$line_thickness = isset($config['symbol']['line_thickness']) ? $config['symbol']['line_thickness'] : '1';

$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $font = isset($_POST['font']) ? $_POST['font'] : 'Montserrat';
    $bg_color = isset($_POST['background_color']) ? $_POST['background_color'] : '#1e2730';
    $grid_color = isset($_POST['grid_color']) ? $_POST['grid_color'] : '#4a667a';
    $text_color = isset($_POST['text_color']) ? $_POST['text_color'] : '#cccccc';
    $size = isset($_POST['size']) ? $_POST['size'] : 'medium';
    $font_size = isset($_POST['font_size']) ? $_POST['font_size'] : '12';
    $line_color = isset($_POST['line_color']) ? $_POST['line_color'] : 'pink';
    $line_thickness = isset($_POST['line_thickness']) ? $_POST['line_thickness'] : '1';

    // Preserve the options block from old JSON
    $options_meta = isset($config['options']) ? $config['options'] : [];

    $new_config = [
        "canvas" => [
            "font" => $font,
            "background_color" => $bg_color,
            "grid_color" => $grid_color,
            "text_color" => $text_color,
            "size" => $size,
            "font_size" => $font_size
        ],
        "symbol" => [
            "line_color" => $line_color,
            "line_thickness" => $line_thickness
        ],
        "options" => $options_meta
    ];

    if (file_put_contents($config_path, json_encode($new_config, JSON_PRETTY_PRINT))) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Error saving settings.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="asset.graph.style.css">
</head>
<body class="tv-config-body">

    <div class="tv-modal">
        
        <?php if ($message): ?>
            <div class="tv-toast"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Header -->
        <div class="tv-header">
            <span class="tv-title">Settings</span>
            <button type="button" class="tv-close" onclick="window.history.back();">&times;</button>
        </div>

        <form action="asset.graph.config.php" method="POST" id="config-form">
            <!-- Body -->
            <div class="tv-body">
                
                <!-- Sidebar Tabs Navigation -->
                <div class="tv-sidebar">
                    <div class="tv-tab-item active" data-tab="tab-canvas">
                        <!-- Canvas Icon (Paint Brush / Screen) -->
                        <svg viewBox="0 0 18 18" width="16" height="16">
                            <path d="M1 3h16v12H1V3zm2 2v8h12V5H3zm7 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 3.5h2v2H9V10z" />
                        </svg>
                        <span>Canvas</span>
                    </div>
                    <div class="tv-tab-item" data-tab="tab-symbol">
                        <!-- Symbol Icon (Candlestick/Line) -->
                        <svg viewBox="0 0 18 18" width="16" height="16">
                            <path d="M4 2h2v4H4V2zm0 10h2v4H4v-4zm8-8h2v5h-2V4zm0 9h2v3h-2v-3zM8 1h2v7H8V1zm0 9h2v7H8v-7z" />
                        </svg>
                        <span>Symbol</span>
                    </div>
                </div>

                <!-- Tab Contents Container -->
                <div class="tv-content-container">
                    
                    <!-- TAB 1: CANVAS -->
                    <div class="tv-tab-content active" id="tab-canvas">
                        <div class="tv-section-title">Scales</div>
                        
                        <div class="tv-row">
                            <span class="tv-label">Text (Axes/Legends)</span>
                            <div class="tv-controls">
                                <select name="font_size" id="font_size" class="tv-select" style="width: 70px; min-width: 70px;">
                                    <?php 
                                    $font_size_list = isset($config['options']['font_sizes']) ? $config['options']['font_sizes'] : ['10', '12', '14', '16', '18', '20'];
                                    foreach ($font_size_list as $fs): 
                                    ?>
                                        <option value="<?php echo $fs; ?>" <?php echo ($font_size === $fs) ? 'selected' : ''; ?>>
                                            <?php echo $fs; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="tv-color-box">
                                    <input type="color" id="text-color-picker" value="<?php echo $text_color; ?>">
                                    <input type="text" name="text_color" id="text_color" value="<?php echo $text_color; ?>" hidden>
                                </div>
                            </div>
                        </div>

                        <div class="tv-row">
                            <span class="tv-label">Font Family</span>
                            <div class="tv-controls">
                                <select name="font" id="font" class="tv-select">
                                    <?php 
                                    $font_list = isset($config['options']['fonts']) ? $config['options']['fonts'] : [];
                                    foreach ($font_list as $f): 
                                    ?>
                                        <option value="<?php echo $f; ?>" <?php echo ($font === $f) ? 'selected' : ''; ?>><?php echo $f; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="tv-section-title" style="margin-top: 10px;">Chart Basic Styles</div>
                        
                        <div class="tv-row">
                            <span class="tv-label">Background (Theme)</span>
                            <div class="tv-controls">
                                <select id="theme_preset" class="tv-select">
                                    <option value="">-- Custom --</option>
                                    <?php 
                                    $color_list = isset($config['options']['colors']) ? $config['options']['colors'] : [];
                                    foreach ($color_list as $theme): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars(json_encode($theme)); ?>">
                                            <?php echo $theme['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="tv-color-box">
                                    <input type="color" id="bg-color-picker" value="<?php echo $bg_color; ?>">
                                    <input type="text" name="background_color" id="background_color" value="<?php echo $bg_color; ?>" hidden>
                                    <input type="text" name="grid_color" id="grid_color" value="<?php echo $grid_color; ?>" hidden>
                                </div>
                            </div>
                        </div>

                        <div class="tv-row">
                            <span class="tv-label">Chart Size</span>
                            <div class="tv-controls">
                                <select name="size" id="size" class="tv-select">
                                    <?php 
                                    $size_list = isset($config['options']['sizes']) ? $config['options']['sizes'] : [];
                                    foreach ($size_list as $s): 
                                    ?>
                                        <option value="<?php echo $s['value']; ?>" <?php echo ($size === $s['value']) ? 'selected' : ''; ?>>
                                            <?php echo $s['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SYMBOL -->
                    <div class="tv-tab-content" id="tab-symbol">
                        <div class="tv-section-title">Symbol Styles</div>
                        
                        <div class="tv-row">
                            <span class="tv-label">Line Color</span>
                            <div class="tv-controls">
                                <select id="line_color_preset" class="tv-select">
                                    <option value="">-- Custom --</option>
                                    <?php 
                                    $line_color_list = isset($config['options']['line_colors']) ? $config['options']['line_colors'] : [];
                                    foreach ($line_color_list as $lc): 
                                    ?>
                                        <option value="<?php echo $lc['value']; ?>" <?php echo ($line_color === $lc['value']) ? 'selected' : ''; ?>>
                                            <?php echo $lc['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="tv-color-box">
                                    <input type="color" id="line-color-picker" value="<?php echo $line_color; ?>">
                                    <input type="text" name="line_color" id="line_color" value="<?php echo $line_color; ?>" hidden>
                                </div>
                            </div>
                        </div>

                        <div class="tv-row">
                            <span class="tv-label">Line Thickness</span>
                            <div class="tv-controls">
                                <input type="range" class="tv-range" name="line_thickness" id="line_thickness" min="0.1" max="2" step="0.1" value="<?php echo $line_thickness; ?>">
                                <span class="tv-range-val" id="thickness-val"><?php echo $line_thickness; ?>px</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="tv-footer">
                <div class="tv-footer-left">
                </div>
                <div class="tv-footer-right">
                    <button type="button" class="tv-btn tv-btn-cancel" onclick="window.history.back();">Cancel</button>
                    <button type="submit" class="tv-btn tv-btn-ok">Ok</button>
                </div>
            </div>
        </form>

    </div>

    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.tv-tab-item');
        const contents = document.querySelectorAll('.tv-tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs & content
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const targetTab = tab.getAttribute('data-tab');
                document.getElementById(targetTab).classList.add('active');
            });
        });

        // Sync color pickers with hidden inputs
        function setupColorSync(pickerId, inputId) {
            const picker = document.getElementById(pickerId);
            const input = document.getElementById(inputId);

            picker.addEventListener('input', function() {
                input.value = this.value;
            });
        }

        setupColorSync('bg-color-picker', 'background_color');
        setupColorSync('text-color-picker', 'text_color');
        setupColorSync('line-color-picker', 'line_color');

        // Theme Preset
        const themePreset = document.getElementById('theme_preset');
        themePreset.addEventListener('change', function() {
            if (this.value) {
                const theme = JSON.parse(this.value);
                
                document.getElementById('background_color').value = theme.background;
                document.getElementById('bg-color-picker').value = theme.background;
                
                document.getElementById('grid_color').value = theme.grid;
                
                document.getElementById('text_color').value = theme.text;
                document.getElementById('text-color-picker').value = theme.text;
            }
        });

        // Line Color Preset
        const lineColorPreset = document.getElementById('line_color_preset');
        lineColorPreset.addEventListener('change', function() {
            if (this.value) {
                document.getElementById('line_color').value = this.value;
                document.getElementById('line-color-picker').value = this.value;
            }
        });

        // Sync line thickness label
        const thicknessSlider = document.getElementById('line_thickness');
        const thicknessVal = document.getElementById('thickness-val');

        thicknessSlider.addEventListener('input', function() {
            thicknessVal.textContent = this.value + 'px';
        });

        // Hide toast notification after 3 seconds
        const toast = document.querySelector('.tv-toast');
        if (toast) {
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // BroadcastChannel to synchronize changes with the chart in real-time
        const syncChannel = new BroadcastChannel('chart_settings_sync');

        function broadcastSettings() {
            // Small delay to ensure other color syncing scripts have completed
            setTimeout(() => {
                const settings = {
                    font: document.getElementById('font').value,
                    background_color: document.getElementById('background_color').value,
                    grid_color: document.getElementById('grid_color').value,
                    text_color: document.getElementById('text_color').value,
                    size: document.getElementById('size').value,
                    font_size: document.getElementById('font_size').value,
                    line_color: document.getElementById('line_color').value,
                    line_thickness: document.getElementById('line_thickness').value
                };
                syncChannel.postMessage(settings);
                // Save to localStorage to persist state if the chart is reloaded
                localStorage.setItem('chart_settings_preview', JSON.stringify(settings));
            }, 50);
        }

        // Add listeners to all relevant inputs
        const inputsToWatch = [
            'font', 'background_color', 'grid_color', 'text_color', 
            'size', 'font_size', 'line_color', 'line_thickness'
        ];

        inputsToWatch.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', broadcastSettings);
                el.addEventListener('change', broadcastSettings);
            }
        });

        // Add listeners to color pickers and preset selects
        document.getElementById('bg-color-picker').addEventListener('input', broadcastSettings);
        document.getElementById('text-color-picker').addEventListener('input', broadcastSettings);
        document.getElementById('line-color-picker').addEventListener('input', broadcastSettings);
        
        themePreset.addEventListener('change', broadcastSettings);
        lineColorPreset.addEventListener('change', broadcastSettings);

        // If settings saved successfully, clear temporary preview from localStorage
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('saved') === 'true') {
            localStorage.removeItem('chart_settings_preview');
        }
    </script>
</body>
</html>
