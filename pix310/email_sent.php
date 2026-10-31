
<link rel="stylesheet" href="pix-style.css?v=<?php echo time(); ?>">

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

<html lang="">
<head>
</head>
<body>

<div class="plata-card-xs">
<h3>exmachina lite</h3>
<div style="flex: 1; display: flex; flex-direction: column;">
            
<form id="form1" method="POST">
<div class="div-label"><label for="email">Email:</label></div>
<div style="position: relative;">
    <input type="email" id="email" name="email" required autocomplete="off">
    <ul id="custom-autofill-list" class="custom-autofill-list" style="display: none;"></ul>
</div><br>
<hr width="95%" class="hrline"/>
<button type="submit" class="buttonBuyNow" name="send_email">Enviar Código</button>
<hr width="95%" class="hrline"/>
<a href="email?cancel=true" class="cancel-link">Cancelar</a>
</form>
    </div>
</div>
    <?php include __DIR__ . '/version.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const emailInput = document.getElementById('email');
    const autofillList = document.getElementById('custom-autofill-list');
    const form = document.getElementById('form1');
    
    let savedEmails = JSON.parse(localStorage.getItem('plata_saved_emails') || '[]');
    
    function showDropdown() {
        if (savedEmails.length === 0) return;
        
        autofillList.innerHTML = '';
        const filterText = emailInput.value.toLowerCase();
        
        let hasMatches = false;
        savedEmails.forEach(email => {
            if (email.toLowerCase().includes(filterText) || filterText === '') {
                hasMatches = true;
                const li = document.createElement('li');
                li.textContent = email;
                li.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    emailInput.value = email;
                    autofillList.style.display = 'none';
                });
                autofillList.appendChild(li);
            }
        });
        
        if (hasMatches) {
            autofillList.style.display = 'block';
        } else {
            autofillList.style.display = 'none';
        }
    }
    
    emailInput.addEventListener('focus', showDropdown);
    emailInput.addEventListener('input', showDropdown);
    emailInput.addEventListener('blur', function() {
        autofillList.style.display = 'none';
    });
    
    form.addEventListener('submit', function() {
        const newEmail = emailInput.value.trim();
        if (newEmail && !savedEmails.includes(newEmail)) {
            savedEmails.unshift(newEmail);
            if (savedEmails.length > 5) savedEmails.pop();
            localStorage.setItem('plata_saved_emails', JSON.stringify(savedEmails));
        }
    });
});
</script>
</body>

