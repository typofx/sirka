<?php
$length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 16;
if ($length < 8) {
    $length = 8;
} elseif ($length > 64) {
    $length = 64;
}

$isSubmitted = isset($_REQUEST['submitted']);
$useUpper    = $isSubmitted ? isset($_REQUEST['uppercase']) : true;
$useLower    = $isSubmitted ? isset($_REQUEST['lowercase']) : true;
$useNumbers  = $isSubmitted ? isset($_REQUEST['numbers'])   : true;

if (!$useUpper && !$useLower && !$useNumbers) {
    $useNumbers = true;
}

function generatePassword($length, $useUpper, $useLower, $useNumbers) {
    $digits = '0123456789';
    $lowers = 'abcdefghijklmnopqrstuvwxyz';
    $uppers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    if ($useUpper && $useLower) {
        $firstCharPool = $lowers . $uppers;
    } elseif ($useUpper) {
        $firstCharPool = $uppers;
    } elseif ($useLower) {
        $firstCharPool = $lowers;
    } else {
        $firstCharPool = $lowers . $uppers;
    }

    $firstChar = $firstCharPool[random_int(0, strlen($firstCharPool) - 1)];

    $pool = '';
    $guaranteed = [];

    if ($useUpper) {
        $pool .= $uppers;
        if (!ctype_upper($firstChar)) {
            $guaranteed[] = $uppers[random_int(0, strlen($uppers) - 1)];
        }
    }
    if ($useLower) {
        $pool .= $lowers;
        if (!ctype_lower($firstChar)) {
            $guaranteed[] = $lowers[random_int(0, strlen($lowers) - 1)];
        }
    }
    if ($useNumbers) {
        $pool .= $digits;
        $guaranteed[] = $digits[random_int(0, strlen($digits) - 1)];
    }

    if ($pool === '') {
        $pool = $digits;
    }

    $remainingLength = $length - 1;
    $bodyChars = $guaranteed;

    while (count($bodyChars) < $remainingLength) {
        $bodyChars[] = $pool[random_int(0, strlen($pool) - 1)];
    }

    if (count($bodyChars) > $remainingLength) {
        $bodyChars = array_slice($bodyChars, 0, $remainingLength);
    }

    $totalBody = count($bodyChars);
    for ($i = $totalBody - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $temp = $bodyChars[$i];
        $bodyChars[$i] = $bodyChars[$j];
        $bodyChars[$j] = $temp;
    }

    return $firstChar . implode('', $bodyChars);
}

$shouldGenerate = isset($_REQUEST['generate']);
$generatedPassword = $shouldGenerate ? generatePassword($length, $useUpper, $useLower, $useNumbers) : '';

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['password' => generatePassword($length, $useUpper, $useLower, $useNumbers)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PassGen - Password Generator</title>
</head>
<body>

<pre>
======================================================
  [PASSGEN] - PASSWORD GENERATOR
======================================================
</pre>

<div class="generator-container">
    <form id="gen-form" method="GET" action="">
        <input type="hidden" name="submitted" value="1">
        <input type="hidden" name="generate" value="1">

        <pre>
<div class="password-box">
[ <input type="text" id="password-display" class="password-input" readonly placeholder="Click GEN" value="<?php echo htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8'); ?>" size="36"> ] <button type="button" id="generate-btn" class="btn-generate" onclick="generateNewPassword()">GEN</button> <button type="button" id="copy-btn" class="btn-copy" onclick="copyPassword()">COPY</button>
</div>
<span id="copy-status" class="status-msg"></span>

------------------------------------------------------
SETTINGS:
------------------------------------------------------
<div class="settings-panel">
<div class="length-control">
<label for="length-range">Password Length: <strong id="length-val"><?php echo $length; ?></strong></label>
<br>
[8] <input type="range" id="length-range" class="length-slider" name="length" min="8" max="64" value="<?php echo $length; ?>" oninput="updateLength(this.value)"> [64]
</div>

<br>
<div class="character-options">
Characters Used:
<br>
<label><input type="checkbox" name="uppercase" value="1" id="chk-upper" class="opt-checkbox" <?php echo $useUpper ? 'checked' : ''; ?>> [A-Z] Uppercase</label>
<label><input type="checkbox" name="lowercase" value="1" id="chk-lower" class="opt-checkbox" <?php echo $useLower ? 'checked' : ''; ?>> [a-z] Lowercase</label>
<label><input type="checkbox" name="numbers" value="1" id="chk-numbers" class="opt-checkbox" <?php echo $useNumbers ? 'checked' : ''; ?>> [0-9] Numbers</label>
</div>
</div>
        </pre>
    </form>
</div>

<script>
function copyPassword() {
    const input = document.getElementById('password-display');
    const pwdText = input.value;
    const statusSpan = document.getElementById('copy-status');

    if (!pwdText) {
        statusSpan.innerText = "(No password to copy! Click GEN first)";
        setTimeout(() => { statusSpan.innerText = ""; }, 2500);
        return;
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(pwdText).then(showCopied);
    } else {
        input.select();
        try {
            document.execCommand('copy');
            showCopied();
        } catch (err) {
            statusSpan.innerText = "(Failed to copy)";
        }
    }

    function showCopied() {
        statusSpan.innerText = "<< COPIED TO CLIPBOARD! >>";
        setTimeout(() => {
            statusSpan.innerText = "";
        }, 2500);
    }
}

function updateLength(val) {
    document.getElementById('length-val').innerText = val;
}

function generateNewPassword() {
    const len = document.getElementById('length-range').value;
    const upper = document.getElementById('chk-upper').checked ? 1 : 0;
    const lower = document.getElementById('chk-lower').checked ? 1 : 0;
    let nums = document.getElementById('chk-numbers').checked ? 1 : 0;

    if (!upper && !lower && !nums) {
        document.getElementById('chk-numbers').checked = true;
        nums = 1;
    }

    const params = new URLSearchParams({
        ajax: '1',
        generate: '1',
        submitted: '1',
        length: len,
        ...(document.getElementById('chk-upper').checked ? { uppercase: '1' } : {}),
        ...(document.getElementById('chk-lower').checked ? { lowercase: '1' } : {}),
        ...(document.getElementById('chk-numbers').checked ? { numbers: '1' } : {})
    });

    fetch('index.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (data && data.password) {
                document.getElementById('password-display').value = data.password;
            }
        })
        .catch(() => {
            document.getElementById('gen-form').submit();
        });
}
</script>

</body>
</html>
