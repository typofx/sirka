<?php
function processCipher($data, $key) {
    $dataLen = strlen($data);
    $result = '';
    $blockIndex = 0;
    $stream = '';
    $streamOffset = 32;

    for ($i = 0; $i < $dataLen; $i++) {
        if ($streamOffset >= 32) {
            $stream = hash('sha256', $key . ':' . $blockIndex, true);
            $blockIndex++;
            $streamOffset = 0;
        }
        $result .= $data[$i] ^ $stream[$streamOffset];
        $streamOffset++;
    }

    return $result;
}

function encryptSha256($text, $key) {
    if ($text === '') {
        return '';
    }
    if ($key === '') {
        return '[ERROR] Password (key) is required to encrypt.';
    }
    $checkTag = substr(hash('sha256', 'CHECK:' . $key), 0, 8);
    $payload = $checkTag . $text;
    $raw = processCipher($payload, $key);
    return bin2hex($raw);
}

function decryptSha256($cipherHex, $key) {
    $cipherHex = trim($cipherHex);
    if ($cipherHex === '') {
        return '';
    }
    if ($key === '') {
        return '[ERROR] Password (key) is required to decrypt.';
    }
    if (strlen($cipherHex) % 2 !== 0 || !ctype_xdigit($cipherHex)) {
        return '[ERROR] Invalid ciphertext format. Must be a hexadecimal string.';
    }
    $raw = @hex2bin($cipherHex);
    if ($raw === false || strlen($raw) < 8) {
        return '[ERROR] Failed to decode ciphertext.';
    }
    $decrypted = processCipher($raw, $key);
    $expectedTag = substr(hash('sha256', 'CHECK:' . $key), 0, 8);
    $actualTag = substr($decrypted, 0, 8);

    if (!hash_equals($expectedTag, $actualTag)) {
        return '[ERROR] Incorrect password. Decryption failed.';
    }

    return substr($decrypted, 8);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$key = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
$inputText = isset($_REQUEST['inputText']) ? (string)$_REQUEST['inputText'] : '';
$outputText = isset($_REQUEST['outputText']) ? (string)$_REQUEST['outputText'] : '';

if ($action === 'encrypt') {
    $outputText = encryptSha256($inputText, $key);
} elseif ($action === 'decrypt') {
    $targetCipher = $inputText !== '' ? $inputText : $outputText;
    $outputText = decryptSha256($targetCipher, $key);
}

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'action' => $action,
        'result' => $outputText
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encrypter / Decrypter - SHA-256</title>
</head>
<body>

<pre>
======================================================
  [ENCRYPTER / DECRYPTER] - SHA-256
======================================================
</pre>

<form id="crypto-form" method="POST" action="">
    <pre>
------------------------------------------------------
INPUTS:
------------------------------------------------------
Key / Password:
<input type="text" id="key" name="key" size="50" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter visible password...">

Input Text:
<textarea id="inputText" name="inputText" rows="6" cols="70" placeholder="Type text to encrypt or ciphertext to decrypt..."><?php echo htmlspecialchars($inputText, ENT_QUOTES, 'UTF-8'); ?></textarea>

<button type="button" id="btn-encrypt" onclick="callEncrypt()">Encrypt</button> <button type="button" id="btn-decrypt" onclick="callDecrypt()">Decrypt</button>

------------------------------------------------------
OUTPUT (Text / Ciphertext):
------------------------------------------------------
<textarea id="outputText" name="outputText" rows="6" cols="70" placeholder="Result will appear here..."><?php echo htmlspecialchars($outputText, ENT_QUOTES, 'UTF-8'); ?></textarea>
<button type="button" id="copy-btn" onclick="copyOutput()">COPY</button> <span id="copy-status"></span>
    </pre>
    <input type="hidden" id="action-field" name="action" value="">
</form>

<script>
function callEncrypt() {
    const keyVal = document.getElementById('key').value;
    const inputVal = document.getElementById('inputText').value;

    if (!keyVal) {
        alert('Please enter a password (key) to encrypt.');
        return;
    }

    if (!inputVal) {
        alert('Please enter text to encrypt.');
        return;
    }

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'encrypt');
    formData.append('key', keyVal);
    formData.append('inputText', inputVal);

    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.result !== undefined) {
            document.getElementById('outputText').value = data.result;
        }
    })
    .catch(() => {
        document.getElementById('action-field').value = 'encrypt';
        document.getElementById('crypto-form').submit();
    });
}

function callDecrypt() {
    const keyVal = document.getElementById('key').value;
    const inputVal = document.getElementById('inputText').value;
    const outputVal = document.getElementById('outputText').value;

    const cipherToDecrypt = inputVal ? inputVal : outputVal;

    if (!keyVal) {
        alert('Please enter the password (key) to decrypt.');
        return;
    }

    if (!cipherToDecrypt) {
        alert('Please enter ciphertext to decrypt.');
        return;
    }

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'decrypt');
    formData.append('key', keyVal);
    formData.append('inputText', cipherToDecrypt);

    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.result !== undefined) {
            document.getElementById('outputText').value = data.result;
        }
    })
    .catch(() => {
        document.getElementById('action-field').value = 'decrypt';
        document.getElementById('crypto-form').submit();
    });
}


function copyOutput() {
    const outputElem = document.getElementById('outputText');
    const text = outputElem.value;
    const statusSpan = document.getElementById('copy-status');

    if (!text) {
        statusSpan.innerText = "(Nothing to copy!)";
        setTimeout(() => { statusSpan.innerText = ""; }, 2500);
        return;
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showCopied);
    } else {
        outputElem.select();
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
</script>

</body>
</html>
