$(document).ready(function () {
  let timerId;

  // Unified function to load the value via AJAX
  function loadMessage() {
    const $spin = $(".status-spin");
    $spin.addClass("fa-spin");

    $.ajax({
      url: "https://www.typofx.ie/sirka/pauloalves/ajax.json/PLTUSD.json",
      type: "GET",
      dataType: "json",
      cache: false,
      success: function (res) {
        if (res) {
          if (res.PLTUSD) {
            $("#info-price").text(res.PLTUSD + " USD");
          }
          if (res.MKCUSD) {
            $("#info-market-cap").text(res.MKCUSD + " USD");
          }
          const now = new Date();
          const formattedTime = formatLastUpdateDate(now);
          $("#info-last-updated").text(formattedTime);
        }
      },
      complete: function () {
        setTimeout(function () {
          $spin.removeClass("fa-spin");
        }, 500);
      }
    });
  }

  // Starts the automatic update interval
  function startUpdates() {
    if (!timerId) {
      timerId = setInterval(loadMessage, 1000);
    }
  }

  // Stops the automatic update interval
  function stopUpdates() {
    clearInterval(timerId);
    timerId = null;
  }

  // Manage updates visibility state (saves bandwidth and CPU when tab is in background)
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      stopUpdates();
    } else {
      loadMessage();
      startUpdates();
    }
  });

  // Copy contract address logic
  $('#copyAddressBtn').on('click', function() {
    const fullAddress = $(this).data('address');
    navigator.clipboard.writeText(fullAddress).then(() => {
      const $icon = $(this);
      $icon.removeClass('fa-regular fa-copy').addClass('fa-solid fa-check').css('color', '#0E9F6E');
      setTimeout(() => {
        $icon.removeClass('fa-solid fa-check').addClass('fa-regular fa-copy').css('color', '');
      }, 1500);
    });
  });

  // MetaMask — Add PLT Token
  $('.metamask-fox').on('click', function() {
    addPLTToken();
  });

  // Execute immediately and start updates on page load
  loadMessage();
  startUpdates();
});

// MetaMask PLT Token parameters configuration
const PLT_TOKEN = {
  address: '0xC29882a1e969018446A3102434DE27B75c616341',
  symbol: 'PLT',
  decimals: 4,
  image: 'https://plata.ie/images/platatoken200px.png'
};

// Prompts MetaMask to watch/add PLT token
async function addPLTToken() {
  if (typeof window.ethereum === 'undefined') {
    alert('MetaMask not detected! Please install MetaMask first.');
    return;
  }
  try {
    const wasAdded = await ethereum.request({
      method: 'wallet_watchAsset',
      params: {
        type: 'ERC20',
        options: {
          address: PLT_TOKEN.address,
          symbol: PLT_TOKEN.symbol,
          decimals: PLT_TOKEN.decimals,
          image: PLT_TOKEN.image
        }
      }
    });

    if (wasAdded) {
      console.log('PLT token added successfully');
    } else {
      console.log('User cancelled');
    }
  } catch (error) {
    console.error('Failed to add PLT token:', error);
  }
}

// Helper to format Javascript Date into UTC string matching PHP UTC format
function formatLastUpdateDate(date) {
  return date.toUTCString().replace("GMT", "UTC");
}