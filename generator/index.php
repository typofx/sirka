<?php
// Load database configurations and coins
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNG Generator</title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Use local script to avoid CDN or missing folder issues -->
    <script src="html2canvas.min.js"></script>
</head>
<body>
    <h1>⚙️ Automatic PNG Generator (Admin)</h1>
    <p>This panel automatically converts all Iframe graphs into Static Images.</p>
    
    <button id="btn-start" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">Start Manual Generation</button>
    <button id="btn-auto" style="padding: 10px 20px; font-weight: bold; cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 4px; margin-left: 10px;">🚀 Start Auto-Pilot (Loop)</button>
    
    <h3 style="margin-top: 30px;">Generation Logs:</h3>
    <div class="log-box" id="log-box"></div>

    <!-- The Sandbox loads the raw HTML for html2canvas to read -->
    <div id="render-sandbox"></div>

    <script>
        // Pass PHP variables to JS context before engine runs
        window.tickers = <?php echo json_encode($tickers); ?>;
        
        const logBox = document.getElementById('log-box');
        const btnStart = document.getElementById('btn-start');
        const btnAuto = document.getElementById('btn-auto');
        const sandbox = document.getElementById('render-sandbox');

        let autoPilotTimer = null;
        let isAutoPilotOn = false;

        const delay = ms => new Promise(r => setTimeout(r, ms));

        function addLog(msg, isError = false) {
            const p = document.createElement('div');
            p.className = `log-item ${isError ? 'error' : ''}`;
            p.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
            logBox.appendChild(p);
            logBox.scrollTop = logBox.scrollHeight;
        }

        async function fetchGraphHtml(ticker) {
            let response = await fetch(`/assets/graph/${ticker}/index.php?size=mini`);
            if (!response.ok) {
                addLog(`Graph not found for ${ticker}. Using fallback...`);
                response = await fetch(`/assets/graph/TICKER.not.found/index.php?size=mini`);
                if (!response.ok) throw new Error(`Fallback also failed (HTTP ${response.status})`);
            }
            return response.text();
        }

        function prepareSandbox(htmlString, ticker) {
            sandbox.innerHTML = '';
            const doc = new DOMParser().parseFromString(htmlString, 'text/html');
            
            doc.querySelectorAll('style, link[rel="stylesheet"]').forEach(s => sandbox.appendChild(s.cloneNode(true)));
            
            const chartDiv = doc.querySelector('.charts-container');
            if (!chartDiv) throw new Error(`Element .charts-container not found for ${ticker}.`);
            
            sandbox.appendChild(chartDiv.cloneNode(true));
        }

        async function captureGraph() {
            await delay(500);
            const canvas = await html2canvas(sandbox, { backgroundColor: null, scale: 2, useCORS: true });
            return canvas.toDataURL('image/png');
        }

        async function saveToServer(ticker, base64Image) {
            const res = await fetch('save_graph.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticker, image: base64Image })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Unknown save error.');
            return data;
        }

        async function processTicker(ticker) {
            addLog(`Processing ${ticker}...`);
            try {
                const html = await fetchGraphHtml(ticker);
                prepareSandbox(html, ticker);
                const image = await captureGraph();
                await saveToServer(ticker, image);
                addLog(`✅ Success! ${ticker} saved.`);
            } catch (err) {
                addLog(`❌ Failed processing ${ticker}: ${err.message}`, true);
            } finally {
                sandbox.innerHTML = '';
            }
        }

        async function startGeneration() {
            btnStart.disabled = true;
            btnStart.innerText = 'Processing...';
            
            addLog(`Starting batch routine for ${window.tickers.length} coins.`);
            
            for (const ticker of window.tickers) {
                await processTicker(ticker);
                await delay(1000); // 1-second throttle to protect server
            }
            
            addLog(`Generation complete!`);
            
            if (isAutoPilotOn) {
                // Reduced to 5 seconds for faster updates
                addLog(`⏳ Auto-Pilot active. Waiting 5 seconds for the next cycle...`);
                autoPilotTimer = setTimeout(startGeneration, 5000);
            } else {
                btnStart.disabled = false;
                btnStart.innerText = 'Start Manual Generation';
            }
        }

        function toggleAutoPilot() {
            isAutoPilotOn = !isAutoPilotOn;
            if (isAutoPilotOn) {
                btnAuto.innerText = '🛑 Stop Auto-Pilot';
                btnAuto.style.background = '#ef4444';
                startGeneration();
            } else {
                btnAuto.innerText = '🚀 Start Auto-Pilot (Loop)';
                btnAuto.style.background = '#3b82f6';
                clearTimeout(autoPilotTimer);
                addLog('⏸️ Auto-Pilot stopped.');
                btnStart.disabled = false;
                btnStart.innerText = 'Start Manual Generation';
            }
        }

        btnStart.addEventListener('click', startGeneration);
        btnAuto.addEventListener('click', toggleAutoPilot);
    </script>
</body>
</html>
