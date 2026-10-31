<?php
$elapsed = isset($_SESSION['code_sent_time']) ? time() - $_SESSION['code_sent_time'] : 0;
$remaining = max(0, min(59, 60 - $elapsed));
$resend_count = isset($_SESSION['resend_count']) ? $_SESSION['resend_count'] : 0;
$limit_reached = $resend_count >= 2;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Confirmação de E-mail - Plata</title>
    <link rel="stylesheet" href="pix-style.css">
</head>

<body>
    <div class="plata-card">
            <h3>exmachina lite</h3>
            <div style="flex: 1; display: flex; flex-direction: column;">

                <form id="form2" method="POST">
                    <?php
                    if (isset($_SESSION['email'])) {
                        echo '<b style="color: var(--color-text-main); font-family: var(--font-family-main);">' . htmlspecialchars($_SESSION['email']) . '</b><br><br>';
                    }
                    ?>
                    <div class="div-label"><label for="verification_code">Código de Verificação:</label></div>
                    <div><input type="tel" id="verification_code" name="verification_code" maxlength="6" autocomplete="off" required></div>
                    <hr width="95%" class="hrline" />
                    <div><button type="submit" class="buttonBuyNow" name="verify_code">Confirmar</button></div>
                    <hr width="95%" class="hrline" />
                    <a style="margin: 0; <?php if (isset($error_message)) echo 'color: #DD137B; font-weight: 500;'; ?>">
                        <?php echo isset($error_message) ? htmlspecialchars($error_message) : 'Verifique sua caixa de e-mail.'; ?>
                    </a>
                    <div class="timer" style="<?php if ($remaining <= 0) echo 'display: none;'; ?>">Peça um novo código em <span class="timer-highlight" id="timerSpan">00:<?php echo str_pad($remaining, 2, '0', STR_PAD_LEFT); ?></span></div>
                    <div id='showMe' style="text-align: center; <?php if ($remaining <= 0) echo 'display: block;'; ?>">
                        <?php if ($limit_reached): ?>
                            <span class="action-link" style="display: block; color: var(--color-text); cursor: default; pointer-events: none;">Acesse o marcador spam.</span>
                        <?php else: ?>
                            <button type="submit" class="action-link" name="resend_code" formnovalidate>Reenviar Código</button>
                        <?php endif; ?>
                    </div>
                    <hr width="95%" class="hrline" />
                    <a href="email?cancel=true" class="cancel-link">Cancelar</a>
                </form>
                
            </div>
        </div>
        <?php include __DIR__ . '/version.php'; ?>
        <script>
            let timeLeft = <?php echo $remaining; ?>;
            const timerSpan = document.getElementById('timerSpan');
            const showMeDiv = document.getElementById('showMe');
            const timerDiv = document.querySelector('.timer');
            
            const timerInterval = setInterval(function() {
                timeLeft -= 1;
                if(timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerDiv.style.display = 'none';
                    showMeDiv.style.display = 'block';
                } else {
                    let seconds = timeLeft < 10 ? '0' + timeLeft : timeLeft;
                    timerSpan.textContent = '00:' + seconds;
                }
            }, 1000);
        </script>
</html>

