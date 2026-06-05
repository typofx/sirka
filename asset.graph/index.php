<?php
// Load JSON configuration file
$config_path = __DIR__ . '/asset.graph.config.json';
$config = [];
if (file_exists($config_path)) {
    $config = json_decode(file_get_contents($config_path), true);
}

$font = isset($config['canvas']['font']) ? $config['canvas']['font'] : 'Montserrat';
$bg_color = isset($config['canvas']['background_color']) ? $config['canvas']['background_color'] : '#1e2730';
$grid_color = isset($config['canvas']['grid_color']) ? $config['canvas']['grid_color'] : '#4a667a';
$text_color = isset($config['canvas']['text_color']) ? $config['canvas']['text_color'] : '#cccccc';
$size = isset($config['canvas']['size']) ? $config['canvas']['size'] : 'medium';
$font_size = isset($config['canvas']['font_size']) ? $config['canvas']['font_size'] : '12';

$line_color = isset($config['symbol']['line_color']) ? $config['symbol']['line_color'] : 'pink';
$line_thickness = isset($config['symbol']['line_thickness']) ? $config['symbol']['line_thickness'] : '1';

// URL of the JSON file
$json_url = "https://www.plata.ie/plataforma/painel/token-historical-data/token_data.json";

// Fetch data from JSON
$json_data = file_get_contents($json_url);

// Decode JSON to PHP array
$data = json_decode($json_data, true);

// Limit records to 180
$data = array_slice($data, 0, 180);

// Reverse data order so that the most recent dates are processed first
$data = array_reverse($data);

// Variables to assemble the chart points
$filtered_data = array_filter($data, function ($value) {
    return $value['price'] > 0;
});

$min_price = PHP_INT_MAX;
$max_price = PHP_INT_MIN;
$oldest_date = null;
$latest_date = null;

// Find min and max price to adjust chart scale
foreach ($data as $point) {
    $price = $point['price'];
    if ($price > 0) {
        if ($price < $min_price)
            $min_price = $price;
        if ($price > $max_price)
            $max_price = $price;
    }

    // Set oldest and newest date
    if (!$latest_date) {
        $latest_date = $point['date'];
    }
    $oldest_date = $point['date'];
}

// Extract date only
$old_date = strtotime(explode(' ', $oldest_date)[0]);
$diff_in_days_total = ($old_date - strtotime(explode(' ', $latest_date)[0])) / (60 * 60 * 24);
$num_months_temp = max(1, round($diff_in_days_total / 30));

// Align start date with first label date so the first block represents a full month
$last_date = strtotime("-$num_months_temp month", $old_date);

// Initialize a day counter
$days_to_next = 0;

// Loop to find the next day
while (true) {
    // Increment 1 day
    $last_date = strtotime("+1 day", $last_date);
    $current_day = date('d', $last_date);

    // If day is found, terminate the loop
    if ($current_day == date('d', $old_date)) {
        break;
    }
    // Increment day counter
    $days_to_next++;
}

// Convert calendar days of first block to corresponding ratio of chart points
$points_per_day = count($filtered_data) / ($num_months_temp * 30);
$extra_points = $days_to_next * $points_per_day;

$num_points = count($filtered_data) + 1 + $extra_points;
$graph_width = 100;
$graph_height = 70;

// Adjusting chart limits
$price_range = ($max_price - $min_price) * 1.4;
$graph_height = max($graph_height, min($graph_height, $price_range * 10));
$x_step = $graph_width / ($num_points - 1);

$num_points_adjusted = count($filtered_data) + 1;
$x_step_adjusted = $graph_width / ($num_points_adjusted - 1);

// Assemble the 'd' path attribute, converting prices to Y coordinates
$x = 0;
$y_offset = -10;
$path_d = "M";
foreach ($filtered_data as $point) {
    $price = $point['price'];
    $scaled_y = $graph_height - ($price - $min_price) / $price_range * $graph_height + $y_offset;
    $path_d .= "$x,$scaled_y ";
    $x += $x_step_adjusted;
}

// Horizontal Grid
$grid_lines = "";
$num_lines = 9;
$line_spacing = $graph_height / ($num_lines - 1);

for ($i = 0; $i < $num_lines; $i++) {
    $y = $i * $line_spacing;
    $grid_lines .= "<line x1='0' y1='$y' x2='$graph_width' y2='$y' class='horizontal' />\n";
}

$start_date = strtotime($oldest_date);
$end_date = strtotime($latest_date);
// Number of months in the time interval
$diff_in_days = ($start_date - $end_date) / (60 * 60 * 24);
// Rounding number of months
$num_months = round($diff_in_days / 30);
$months = [];

// Iterating over months
$months = [];
for ($i = $num_months; $i >= 0; $i--) {
    if ($i == $num_months || $i == 0) {
        $current_month = date("d-M", strtotime("-$i month", $start_date));
    } else {
        $current_month = date("M", strtotime("-$i month", $start_date));
    }
    $months[] = $current_month;
}

$grid_lines_vertical = "";

// Total available spacing
$total_width = $graph_width - $extra_points * $x_step;

// Total number of intervals (number of lines - 1)
$num_intervals = $num_months;

// Calculate uniform spacing
$uniform_x_step = $total_width / $num_intervals;

// Add first line with extra spacing
$grid_lines_vertical .= "<line x1='0' y1='0' x2='0' y2='$graph_height' class='vertical' />\n";
$x = $extra_points * $x_step;
$grid_lines_vertical .= "<line x1='$x' y1='0' x2='$x' y2='$graph_height' class='vertical' />\n";

// Add remaining lines with uniform spacing
for ($i = 1; $i <= $num_months; $i++) {
    $x = $extra_points * $x_step + $i * $uniform_x_step;
    $grid_lines_vertical .= "<line x1='$x' y1='0' x2='$x' y2='$graph_height' class='vertical' />\n";
}

// Chart line boundary limit to prevent overflow
$dasharray = max(150, $graph_width / 2) + ($num_points * 2);

// Percentage change calculation between newest and oldest record
$oldest_price = $data[0]['price']; // Oldest price (last in the original list)
$latest_price = $data[count($data) - 1]['price']; // Most recent price (first in the original list)

if ($oldest_price > 0) {
    $percentage_change = (($latest_price - $oldest_price) / $oldest_price) * 100;
} else {
    $percentage_change = 0; // Avoid division by zero
}
//echo $percentage_change;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,user-scalable=no">
    <title>Graph</title>
    

    
    <link rel="stylesheet" href="asset.graph.style.css">
    
    <style>
        :root {
            --chart-font-family: '<?php echo $font; ?>', <?php echo ($font === 'Courier New') ? 'monospace' : 'sans-serif'; ?>;
            --chart-bg-color: <?php echo $bg_color; ?>;
            --chart-grid-color: <?php echo $grid_color; ?>;
            --chart-text-color: <?php echo $text_color; ?>;
            --chart-font-size: <?php echo $font_size; ?>px;
            --chart-line-color: <?php echo $line_color; ?>;
            --chart-line-thickness: <?php echo $line_thickness; ?>px;
        }
    </style>
</head>

<body>
    <div class="charts-container size-<?php echo $size; ?> cf">
        <div class="chart" id="graph-1-container">
            <div class="title-container">
                <h2 class="title">Plata (PLTUSDT)</h2>
                <!-- Percentage change -->
                <div class="percentage-change <?php echo ($percentage_change < 0) ? 'negative' : 'positive'; ?>">
                    <span><?php echo ($percentage_change > 0) ? '+' : ''; ?><?php echo number_format($percentage_change, 2); ?>%</span>
                </div>
            </div>
            <div class="chart-svg">
                <!-- Price legend -->
                <div class="price-legend">
                    <text class="legend-y max">$<?php echo number_format($max_price, 8); ?></text>
                    <text class="legend-y min">$<?php echo number_format($min_price, 8); ?></text>
                </div>
                <!-- Dynamic chart adjustment -->
                <svg class="chart-line" id="chart-1"
                    viewBox="0 0 <?php echo $graph_width + 5 ?> <?php echo $graph_height + 5 ?>">
                    <defs>
                        <clipPath id="clip" x="0" y="0" width="<?php echo $graph_width ?>"
                            height="<?php echo $graph_height ?>">
                            <rect id="clip-rect" x="-10" y="0" width="10" height="10" />
                        </clipPath>
                    </defs>

                    <g id="grid">
                        <?php echo $grid_lines; ?>
                        <?php echo $grid_lines_vertical; ?>
                    </g>

                    <!-- Dynamic chart line based on settings -->
                    <path id="graph-1" d="<?php echo $path_d; ?>" stroke="var(--chart-line-color)" stroke-width="var(--chart-line-thickness)"
                        fill="transparent" stroke-dasharray="<?php echo $dasharray; ?>"
                        stroke-dashoffset="<?php echo $dasharray; ?>" />
                </svg>

                <!-- Right legend -->
                <div class="price-legend-right">
                    <?php
                  
                    $price_steps = [
                        $max_price * 1.2,                         // 1. HIGHER PRICE * 1.2
                        $max_price,                               // 2. HIGHER PRICE
                        ($max_price + $min_price) * 0.8,          // 3. (HIGHER PRICE + LOWER PRICE) * 0.8
                        ($max_price + $min_price) * 0.6,          // 4. (HIGHER PRICE + LOWER PRICE) * 0.6
                        ($max_price + $min_price) * 0.5,          // 5. (HIGHER PRICE + LOWER PRICE) * 0.5
                        ($max_price + $min_price) * 0.4,          // 6. (HIGHER PRICE + LOWER PRICE) * 0.4
                        ($max_price + $min_price) * 0.2,          // 7. (HIGHER PRICE + LOWER PRICE) * 0.2
                        $min_price,                               // 8. LOWER PRICE
                        $min_price * 0.8                          // 9. LOWER PRICE * 0.8
                    ];

                    for ($i = 0; $i < $num_lines; $i++):
                        $top_position = ($i / ($num_lines - 1)) * 100;
                        $price_step_value = isset($price_steps[$i]) ? $price_steps[$i] : 0;
                    ?>
                        <h3 class="price-step" style="position: absolute; top: <?php echo $top_position; ?>%;">
                            $<?php echo number_format($price_step_value, 8); ?>
                        </h3>
                    <?php endfor; ?>
                </div>

                <!-- Date legend -->
                <div class="time-legend">
                    <?php foreach ($months as $month): ?>
                        <h3 class="time-month" style="width: <?php echo 100 / count($months); ?>%; display: inline-block;">
                            <?php echo $month; ?>
                        </h3>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to dynamically update CSS variables and chart layout classes
        function updateChartSettings(settings) {
            if (!settings) return;

            // 1. Update font family
            if (settings.font) {
                const fallback = settings.font === 'Courier New' ? 'monospace' : 'sans-serif';
                document.documentElement.style.setProperty('--chart-font-family', `'${settings.font}', ${fallback}`);
            }

            // 2. Update CSS variables in :root
            if (settings.background_color) {
                document.documentElement.style.setProperty('--chart-bg-color', settings.background_color);
            }
            if (settings.grid_color) {
                document.documentElement.style.setProperty('--chart-grid-color', settings.grid_color);
            }
            if (settings.text_color) {
                document.documentElement.style.setProperty('--chart-text-color', settings.text_color);
            }
            if (settings.font_size) {
                document.documentElement.style.setProperty('--chart-font-size', settings.font_size + 'px');
            }
            if (settings.line_color) {
                document.documentElement.style.setProperty('--chart-line-color', settings.line_color);
            }
            if (settings.line_thickness) {
                document.documentElement.style.setProperty('--chart-line-thickness', settings.line_thickness + 'px');
            }

            // 3. Modify container size class
            if (settings.size) {
                const container = document.querySelector('.charts-container');
                if (container) {
                    container.classList.remove('size-small', 'size-medium', 'size-large', 'size-responsive');
                    container.classList.add('size-' + settings.size);
                }
            }
        }

        // Try to apply saved preview settings from localStorage on page load
        try {
            const savedPreview = localStorage.getItem('chart_settings_preview');
            if (savedPreview) {
                updateChartSettings(JSON.parse(savedPreview));
            }
        } catch (e) {
            console.error("Error reading local settings:", e);
        }

        // Initialize BroadcastChannel to listen to real-time simultaneous updates
        const syncChannel = new BroadcastChannel('chart_settings_sync');
        syncChannel.onmessage = (event) => {
            if (event.data) {
                updateChartSettings(event.data);
            }
        };

        // Clear temporary localStorage preview when settings window sends success (saved)
        // This prevents previews from getting stuck after closing the modal.
        // PHP saves permanently, so reloading index will read the final JSON.
        window.addEventListener('storage', (e) => {
            if (e.key === 'chart_settings_preview' && !e.newValue) {
                // If cleared, reload to fetch default state from server
                location.reload();
            }
        });
    </script>
</body>

</html>