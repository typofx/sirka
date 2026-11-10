$(document).ready(function() {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'assetsTable') {
            return true;
        }
        var showFiats = $('#showFiatsCheckbox').is(':checked');
        if (showFiats) {
            return true;
        }
        var network = (data[7] || '').trim().toLowerCase();
        return network !== 'fiduciary coin';
    });

    var table = $('#assetsTable').DataTable({
        "lengthMenu": [ [25, 50, 75, -1], [25, 50, 75, "All"] ],
        "pageLength": 50,
        "rowCallback": function(row, data, index) {
            var priceStr = data[8] ? data[8].replace(/,/g, '').trim() : '';
            var priceVal = parseFloat(priceStr);
            if (priceVal === 0) {
                $('td:eq(8)', row).css('color', 'red');
            }
        }
    });

    $('#showFiatsCheckbox').on('change', function() {
        table.draw();
    });

    var priceCache = {};
    var hasUserChangedCurrency = false;

    $('#assetsTable tbody .fiat-price').each(function() {
        var $priceCell = $(this);
        var initialNmid = $priceCell.data('nmid');
        var initialText = $priceCell.text().trim();
        if (initialNmid && initialText && initialText !== '-' && initialText !== '...') {
            priceCache[initialNmid + '_USD'] = initialText;
        }
    });

    function setCellPrice($target, formattedValue, isInvalidOrZero) {
        $target.text(formattedValue);
        $target.css('color', isInvalidOrZero ? 'red' : '');
    }

    function updatePrices() {
        var selectedTicker = $('#priceCurrencySelect').val();
        if (!selectedTicker) return;

        $('#assetsTable tbody tr').each(function() {
            var $row = $(this);
            var $cell = $row.find('.fiat-price');
            if (!$cell.length) return;

            var nmid = $cell.data('nmid');
            var rowTicker = $cell.data('ticker');

            if (!nmid) return;

            if (rowTicker && rowTicker.toUpperCase() === selectedTicker.toUpperCase()) {
                setCellPrice($cell, '1.00000000', false);
                return;
            }

            var cacheKey = nmid + '_' + selectedTicker;
            if (priceCache[cacheKey] !== undefined) {
                var cached = priceCache[cacheKey];
                var cachedNum = parseFloat(String(cached).replace(/,/g, '').trim());
                setCellPrice($cell, cached, isNaN(cachedNum) || cachedNum === 0);
                return;
            }

            $cell.text('...');

            var isTypoFx = window.location.hostname.indexOf('typofx.ie') !== -1;
            var baseUrl = isTypoFx ? '/.scr/library/json/' : 'https://www.typofx.ie/.scr/library/json/';
            var url = baseUrl + encodeURIComponent(nmid) + '/' + encodeURIComponent(selectedTicker) + '.json';

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                cache: false
            }).done(function(data) {
                var price = (data && data.pc !== undefined && data.pc !== '') ? data.pc : null;
                var parsedNum = (price !== null) ? parseFloat(String(price).replace(/,/g, '').trim()) : NaN;
                var formatted = (!isNaN(parsedNum)) ? parsedNum.toFixed(8) : '0.00000000';
                priceCache[cacheKey] = formatted;
                setCellPrice($cell, formatted, isNaN(parsedNum) || parsedNum === 0);
            }).fail(function() {
                var fallbackPrice = '0.00000000';
                priceCache[cacheKey] = fallbackPrice;
                setCellPrice($cell, fallbackPrice, true);
            });
        });
    }

    $('#priceCurrencySelect').on('click mousedown touchstart focus', function(e) {
        e.stopPropagation();
    });

    $('#priceCurrencySelect').on('change', function() {
        hasUserChangedCurrency = true;
        updatePrices();
    });

    table.on('draw', function() {
        if (hasUserChangedCurrency) {
            updatePrices();
        }
    });
});
