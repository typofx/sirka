// Global scope for quotation
let _preferredPair = "PLTBRL"; // DEFINE PRIORITY HERE: "PLTUSD" or "PLTBRL"
let _PLTBRL = 0.00000001; // Default fallback value
let _PLT_FT = ""; // New field for the exact inverted value (ft)
let _currency = _preferredPair.replace("PLT", ""); // Automatically extracts "BRL" or "USD"

// Global functions called by HTML (onkeyup / onkeypress)
function BRLexec() {
    var valorpixEl = document.getElementById("valorpix");
    var pltWantedEl = document.getElementById("PLTwanted");
    if (valorpixEl && pltWantedEl) {
        var textBRL = Number(valorpixEl.value);
        if (_PLT_FT !== "") {
            var ftValue = parseFloat(String(_PLT_FT).replace(/,/g, ''));
            pltWantedEl.value = Number(textBRL * ftValue).toFixed(4);
        } else {
            pltWantedEl.value = Number(textBRL / _PLTBRL).toFixed(4);
        }
    }
}

function addZeroBRL() {
    var valorpixEl = document.getElementById("valorpix");
    if (valorpixEl) {
        valorpixEl.value = Number(valorpixEl.value).toFixed(2);
    }
}

function PLTexec() {
    var valorpixEl = document.getElementById("valorpix");
    var pltWantedEl = document.getElementById("PLTwanted");
    if (valorpixEl && pltWantedEl) {
        var textPLT = Number(pltWantedEl.value);
        if (_PLT_FT !== "") {
            var ftValue = parseFloat(String(_PLT_FT).replace(/,/g, ''));
            valorpixEl.value = Number(textPLT / ftValue).toFixed(2);
        } else {
            valorpixEl.value = Number(textPLT * _PLTBRL).toFixed(2);
        }
    }
}

function formatEtherWallet(input) {
    if (!input) return;
    var val = input.value.trim();
    if (!val) {
        input.style.borderColor = "";
        input.setCustomValidity("");
        return;
    }
    
    // Normalize prefix and remove invalid characters
    if (val.startsWith("0x") || val.startsWith("0X")) {
        var hexPart = val.slice(2).replace(/[^0-9a-fA-F]/g, "");
        val = "0x" + hexPart;
    } else {
        var hexOnly = val.replace(/[^0-9a-fA-F]/g, "");
        val = hexOnly.length > 0 ? "0x" + hexOnly : "";
    }
    
    // Limit to 42 characters (0x + 40 hex digits)
    if (val.length > 42) {
        val = val.slice(0, 42);
    }
    
    input.value = val;
    input.style.borderColor = "";
    input.setCustomValidity("");
}

function isValidEtherWallet() {
    var web3walletEl = document.getElementById("web3wallet");
    if (!web3walletEl) return true;
    
    var address = web3walletEl.value.trim();
    if (!address) {
        web3walletEl.style.borderColor = "";
        web3walletEl.setCustomValidity("");
        return false;
    }
    
    var isValid = /^0x[a-fA-F0-9]{40}$/.test(address);
    if (isValid && typeof Web3 !== 'undefined' && Web3 && Web3.utils && typeof Web3.utils.isAddress === 'function') {
        try {
            isValid = Web3.utils.isAddress(address);
        } catch (e) {
            // Keep regex result if Web3.utils throws
        }
    }
    
    if (!isValid) {
        web3walletEl.style.borderColor = "var(--color-danger, #d9534f)";
        web3walletEl.setCustomValidity("Por favor, informe uma carteira Polygon (EVM) válida começando com 0x.");
        web3walletEl.reportValidity();
        return false;
    } else {
        web3walletEl.style.borderColor = "";
        web3walletEl.setCustomValidity("");
        return true;
    }
}

function updateRateDisplay() {
    var ratePairEl = document.getElementById("rate-pair-display");
    if (ratePairEl) {
        if (_PLT_FT !== "") {
            // Uses the exact string from the API
            ratePairEl.innerText = "1 " + _currency + " = " + _PLT_FT + " PLT";
        }
    }
}

// Safe initialization after DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Sets default value to 10 BRL
    var valorpixEl = document.getElementById("valorpix");
    if (valorpixEl && (valorpixEl.value === "" || valorpixEl.value === "0" || valorpixEl.value === "0.00" || valorpixEl.value === "0,00")) {
        valorpixEl.value = "10.00";
    }

    // Price table text initialization (order.php)
    var txtCurrencyEnvEl = document.getElementById("txtCurrencyEnv");
    if (txtCurrencyEnvEl) {
        txtCurrencyEnvEl.innerText = "(" + _currency + ")";
    }
    var txtPAIREl = document.getElementById("txtPAIR");
    if (txtPAIREl) {
        txtPAIREl.innerText = _PLTBRL.toFixed(8);
    }

    // Form submission validation for Polygon wallet
    var form1 = document.getElementById("form1");
    if (form1) {
        form1.addEventListener("submit", function (e) {
            if (!isValidEtherWallet()) {
                e.preventDefault();
                var web3walletEl = document.getElementById("web3wallet");
                if (web3walletEl) {
                    web3walletEl.focus();
                    web3walletEl.reportValidity();
                }
                return false;
            }
        });
    }


    // 1. ORDER PAGE CLOCK (order.php)
    // Dynamic clock that keeps "Valid Until" always updated to 5 minutes in the future
    var timerEl = document.getElementById("countdown-timer");
    if (timerEl) {
        function updateFutureTime() {
            var now = new Date();
            var future = new Date(now.getTime() + 600 * 1000); // 10 minutes in the future

            // Força o horário para BRT (UTC-3), ignorando o fuso horário local do usuário
            var utc = future.getTime() + (future.getTimezoneOffset() * 60000);
            var futureBRT = new Date(utc + (3600000 * -3));

            var hours = futureBRT.getHours();
            var minutes = futureBRT.getMinutes();
            var seconds = futureBRT.getSeconds();
            var day = futureBRT.getDate();
            var month = futureBRT.getMonth() + 1;
            var year = futureBRT.getFullYear();

            hours = hours < 10 ? "0" + hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            day = day < 10 ? "0" + day : day;
            month = month < 10 ? "0" + month : month;

            timerEl.innerText = hours + ":" + minutes + ":" + seconds + " BRT " + day + "/" + month + "/" + year;
        }
        updateFutureTime();
        setInterval(updateFutureTime, 1000);
    }

    // 2. CHECKOUT PAGE COUNTDOWN TIMER - Display only, does not control state
    // endTime will be synced with the database 'date' field on the first AJAX call
    // This ensures the timer uses the same reference as the bot to calculate expiration
    var checkoutTimerEl = document.getElementById("checkout-timer");
    if (checkoutTimerEl) {
        // Initial PHP value as fallback (before the first AJAX response)
        var expAttr = checkoutTimerEl.getAttribute("data-expiration");
        var endTime = expAttr ? parseInt(expAttr) * 1000 : (Date.now() + 600000);
        var timerSynced = false; // flag: has it synced with the database?

        function updateTimer() {
            var now = Date.now();
            var remaining = Math.max(0, Math.floor((endTime - now) / 1000));

            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            checkoutTimerEl.innerText = minutes + ":" + seconds;

            // Quando chegar a zero, exibe "Expirado" e dispara a ação na interface
            if (remaining <= 0) {
                clearInterval(interval);
                checkoutTimerEl.innerText = "Expirado";

                // Puxa referências necessárias se existirem
                if (typeof paymentCheckTimer !== 'undefined') {
                    clearInterval(paymentCheckTimer);
                }
                
                var identify = document.getElementById('checkIdentify') ? document.getElementById('checkIdentify').value : '';
                var email = document.getElementById('userEmail') ? document.getElementById('userEmail').value : null;

                // Esconde QR Code/Processing e mostra a mensagem de Expirado
                $('#qrCodeWrapper, #processingContainer').filter(':visible').fadeOut(400).promise().done(function() {
                    $('#expiredContainer').fadeIn(400);
                });
                
                // Envia e-mail de expiração apenas uma vez
                if (email && typeof window._expiredEmailSent === 'undefined') {
                    window._expiredEmailSent = true;
                    $.post('send_expired_email.php', { email: email });
                }

                console.log('Payment expired for identify: ' + identify);
            }
        }

        updateTimer();
        var interval = setInterval(updateTimer, 1000);
    }
});


// AJAX logic to load real-time quotation (Only in order.php)
if (document.getElementById("valorpix")) {
    $(document).ready(function () {
        let timerId;

        function loadPrices() {
            var requestedFiat = _preferredPair.replace("PLT", "");
            $.ajax({
                url: "https://www.typofx.ie/.scr/library/json/plata.token/" + requestedFiat + ".json",
                type: "GET",
                dataType: "json",
                cache: false,
                success: function (res) {
                    if (res && res.pc) {
                        _PLTBRL = parseFloat(res.pc);
                        if (res.ft !== undefined && res.ft !== null) {
                            _PLT_FT = res.ft;
                        }
                        _currency = requestedFiat;

                        // Updates the value in the price table
                        var txtPAIREl = document.getElementById("txtPAIR");
                        if (txtPAIREl) {
                            txtPAIREl.innerText = _PLTBRL.toFixed(8);
                        }

                        // Updates the currency legend in the table header (e.g., BRL or USD)
                        var txtCurrencyEnvEl = document.getElementById("txtCurrencyEnv");
                        if (txtCurrencyEnvEl) {
                            txtCurrencyEnvEl.innerText = "(" + _currency + ")";
                        }

                        // Updates the Market Cap if available
                        var txtMarketcapEl = document.getElementById("txtMarketcap");
                        if (txtMarketcapEl && res.mk !== undefined) {
                            txtMarketcapEl.innerText = "Market Cap $ " + parseFloat(res.mk).toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
                        }

                        // Recalculates the expected PLT value based on currently inserted BRL
                        BRLexec();
                        updateRateDisplay();
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error loading PLT quotations:", error);
                }
            });
        }

        function startUpdates() {
            if (!timerId) {
                timerId = setInterval(loadPrices, 1000); // Updates every 1 second
            }
        }

        function stopUpdates() {
            clearInterval(timerId);
            timerId = null;
        }

        // Background optimization using the Page Visibility API
        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                stopUpdates();
            } else {
                loadPrices();
                startUpdates();
            }
        });

        // Initial load and start loops
        loadPrices();
        BRLexec();
        updateRateDisplay();
        startUpdates();
    });
}

// Global reference for payment check timer
var paymentCheckTimer;

// AJAX logic to poll payment status (Only in checkout page)
$(document).ready(function () {
    if (document.getElementById('checkIdentify')) {
        var identify = document.getElementById('checkIdentify').value;
        var email = document.getElementById('userEmail').value;
        var amount = document.getElementById('checkAmount').value;
        var currentPaymentPhase = 'processing';
        
        paymentCheckTimer = setInterval(function() {
            $.ajax({
                url: 'check_payment.php',
                type: 'GET',
                data: {
                    identify: identify,
                    email: email,
                    amount: amount,
                    phase: currentPaymentPhase
                },
                dataType: 'json',
                success: function (res) {
                    console.log("Payment check response: ", res);

                    // Sync timer with database 'date' on the first response
                    // Uses the same reference as the bot to calculate expiration
                    if (res && res.date && typeof timerSynced !== 'undefined' && !timerSynced) {
                        // Replace '-' with '/' for broader browser compatibility (e.g. Safari)
                        var safeDateStr = res.date.replace(/-/g, '/');
                        var dbDateMs = new Date(safeDateStr + ' UTC').getTime();
                        if (!isNaN(dbDateMs)) {
                            endTime = dbDateMs + 600 * 1000; // DB date + 10 min
                            timerSynced = true;
                            console.log('Timer synced with DB date:', res.date);
                        }
                    }

                    if (res && res.status === 'matched') {
                        // Bot confirmed Pix received in processing.json
                        if (currentPaymentPhase === 'processing') {
                            currentPaymentPhase = 'paid'; // Change phase to paid now!
                            if ($('#processingContainer').is(':hidden')) {
                                $('#qrCodeWrapper, #expiredContainer').filter(':visible').fadeOut(400).promise().done(function() {
                                    $('#processingContainer').fadeIn(400);
                                });
                                // Register when the processing screen started showing
                                window._processingStartTime = Date.now();
                            }
                        }
                        console.log('Payment matched for identify: ' + identify + '. Waiting for success...');
                        // DO NOT stop the timer! Keep polling to catch "success" from paid.json
                    } else if (res && (res.status === 'success' || res.status === 'paid')) {
                        clearInterval(paymentCheckTimer);
                        
                        // Ensure the loading screen is visible for at least 5 seconds
                        let elapsedTime = window._processingStartTime ? (Date.now() - window._processingStartTime) : 0;
                        let remainingDelay = Math.max(0, 5000 - elapsedTime);
                        
                        // If skipped directly to success without passing through matched (Should not happen now, but safe fallback)
                        if ($('#processingContainer').is(':hidden')) {
                            $('#qrCodeWrapper, #expiredContainer').filter(':visible').fadeOut(400).promise().done(function() {
                                $('#processingContainer').fadeIn(400);
                            });
                            remainingDelay = 5000; // Force 5 seconds if loading screen wasn't shown
                        }
                        
                        console.log('Payment success! Redirecting in ' + remainingDelay + 'ms...');
                        setTimeout(function() {
                            window.location.href = 'success.php';
                        }, remainingDelay);
                    } else if (res && res.status === 'expired') {
                        clearInterval(paymentCheckTimer);
                        $('#qrCodeWrapper, #processingContainer').filter(':visible').fadeOut(400).promise().done(function() {
                            $('#expiredContainer').fadeIn(400);
                        });
                        
                        // Send expiration email once
                        if (typeof window._expiredEmailSent === 'undefined') {
                            window._expiredEmailSent = true;
                            $.post('send_expired_email.php', { email: email });
                        }

                        console.log('Payment expired for identify: ' + identify);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error checking payment status:', error);
                }
            });
        }, 1000); // Check every 1 second
    }
});
