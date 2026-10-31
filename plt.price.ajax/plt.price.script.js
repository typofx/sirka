$(document).ready(function () {
  let timerId;

  // Main function to load the rates via AJAX
  function loadPrices() {
    $.ajax({
      url: "https://www.typofx.ie/.scr/library/json/plata.token/USD.json",
      type: "GET",
      dataType: "json",
      cache: false,
      success: function (res) {
        if (res) {
          // Update USD Price
          if (res.pc) {
            $(".price.item-usd").text(res.pc);
          }
          // Update USD Market Cap
          if (res.mk) {
            $(".marketcap.cap-usd").text(res.mk);
          }
        }
      }
    });
  }


  // Starts the automatic update interval (every 0.5 second)
  function startUpdates() {
    if (!timerId) {
      timerId = setInterval(loadPrices, 500);
    }
  }

  // Stops the automatic update interval
  function stopUpdates() {
    clearInterval(timerId);
    timerId = null;
  }

  // Page Visibility API to pause requests in background tabs (saves bandwidth and CPU)
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      stopUpdates();
    } else {
      loadPrices();
      startUpdates();
    }
  });

  // Execute immediately and start periodic updates cycle
  loadPrices();
  startUpdates();
});
