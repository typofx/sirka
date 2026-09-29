    $(document).ready(function() {

        var tabela = $('#example').DataTable({
            searchDelay: 0,

            lengthMenu: [
                [50, 100, 150, 200, -1],
                [50, 100, 150, 200, "All"]
            ],

            order: [[0, "asc"]],

            language: {
                search: "",
                searchPlaceholder: "",
                info: "_TOTAL_ Public Providers Registered",
                paginate: {
                    previous: "Previous",
                    next: "Next"
                }
            }
        });

        $('.dataTables_filter input').on('input', function() {
            tabela.search(this.value).draw();
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

        // Render ticker icons
        renderTickerIcons();

        // Render exchange icons
        renderExchangeIcons();

    });

// ------------------------------------------------------------
// Open MetaMask — Add PLT Token
// ------------------------------------------------------------
const PLT_TOKEN = {
    address: '0xC29882a1e969018446A3102434DE27B75c616341',
    symbol: 'PLT',
    decimals: 4,
    image: 'https://plata.ie/images/platatoken200px.png'
};

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

// ------------------------------------------------------------
// Ticker Icons — render pair icons in Ticker column
// Expects: <td class="ticker-cell" data-pair="PLT/USDT"></td>
// ------------------------------------------------------------
const TICKER_ICONS = {
    'PLT':   'https://www.typofx.ie/images/assets-icons/680642ae77599_PlataTokenIconSmall.png',
    'POL':   'https://www.typofx.ie/images/assets-icons/69e0e73596d16_69d138547484c_polygon-token.svg',
    'WMATIC':'https://www.typofx.ie/images/assets-icons/66e87ca220442_wMatic_32.webp',
    'WETH':  'https://www.typofx.ie/images/assets-icons/66e87cb174d65_wETH_32.webp',
    'WBTC':  'https://www.typofx.ie/images/assets-icons/67041086e9f16_wrapped-bitcoin-wbtc-seeklogo.svg',
    'USDC':  'https://www.typofx.ie/images/assets-icons/66e878596b462_3408.png',
    'USDT':  'https://www.typofx.ie/images/assets-icons/66e87d419abc0_tether_32.webp',
    'DAI':   'https://www.typofx.ie/images/assets-icons/66e87d56c8b70_mcdDai_32.png',
    'USTC':  'https://www.typofx.ie/images/assets-icons/66edab8b70f45_7129.png',
    'USDCE': 'https://www.typofx.ie/images/assets-icons/69c20a1318d3c_66e878596b462_3408.png',
    'PAXG':  'https://www.typofx.ie/images/assets-icons/66e87de3af910_paxosgold_32.webp',
    'BUSD':  'https://www.typofx.ie/images/assets-icons/66ee177133c33_busd-logo.png',
    'WBNB':  'https://www.typofx.ie/images/assets-icons/69d26b334447f_wbnb.webp',
    'EURS':  'https://www.typofx.ie/images/assets-icons/66f306d45bca5_stasis-euro-eurs-logo.png',
    'BRL':   'https://www.typofx.ie/images/assets-icons/66fcd3ae2c3f2_fiat-real_br.png',
    'EUR':   'https://www.typofx.ie/images/assets-icons/66fcd07278ae6_fiat-euro.png',
    'USD':   'https://www.typofx.ie/images/assets-icons/66fcd15fd24fa_fiat-dollar.png',
    'EURE':  'https://www.typofx.ie/images/assets-icons/69f80d7c33427_eure-logo.svg',
    'XAUT':  'https://www.typofx.ie/images/assets-icons/699f05e2c17a5_xaut.avif',
    'BRZ':   'https://www.typofx.ie/images/assets-icons/69c358cbb6ad3_66e87cc03eb4f_braziliandigital_32.png',
    'BRLA':  'https://www.typofx.ie/images/assets-icons/69c35b7f7f1cf_brla.png',
    'CRV':   'https://www.typofx.ie/images/assets-icons/69c555cbb98ca_curvefi-crv_32.png',
    'MATIC': 'https://www.typofx.ie/images/assets-icons/69e0e748e9a5f_69d138547484c_polygon-token.svg',
    'WPOL':  'https://www.typofx.ie/images/assets-icons/6a10235a1014b_wpol_icon.webp'
};

function buildTickerIcon(ticker) {
    if (TICKER_ICONS[ticker]) {
        return '<img src="' + TICKER_ICONS[ticker] + '" class="ticker-icon" alt="' + ticker + '" title="' + ticker + '">';
    }
    return '<span class="ticker-text">' + ticker + '</span>';
}

function renderTickerIcons() {
    $('.ticker-cell').each(function () {
        var $cell = $(this);
        var pair = $cell.data('pair');
        if (!pair) return;

        var parts = pair.split('/');
        if (parts.length !== 2) { $cell.text('-'); return; }

        var t0 = parts[0].trim();
        var t1 = parts[1].trim();

        $cell.html('{ ' + buildTickerIcon(t0) + ' : ' + buildTickerIcon(t1) + ' }');
    });
}

// ------------------------------------------------------------
// Exchange Icons — render icon + name in Exchange column
// Expects: <td class="exchange-cell" data-exchange="Sushiswap">Sushiswap</td>
// ------------------------------------------------------------
function getExchangeIcon(name) {
    var slug = name.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');
    return './.img/' + slug + '.svg';
}

function renderExchangeIcons() {
    $('.exchange-cell').each(function () {
        var $cell = $(this);
        var name = $cell.data('exchange');
        if (!name) return;

        var $img = $('<img class="exchange-icon" alt="' + name + '" title="' + name + '">');
        $img.on('error', function () {
            $cell.html('<span class="exchange-fallback">NA</span> ' + name);
        });
        $img.attr('src', getExchangeIcon(name.trim()));

        $cell.html($img).append(' ' + name);
    });
}
