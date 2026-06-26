<?php
    if (empty($inputName)) {
        $inputName = 'airport_id';
    }

    $selected = Request::query($inputName);
    $selectedTitle = '';

    if (!empty($selected)) {
        $airport = \Modules\Flight\Models\Airport::query()
            ->where('code', strtoupper((string) $selected))
            ->orWhere('id', $selected)
            ->first();

        if ($airport) {
            $code = strtoupper((string) $airport->code);
            $selected = $code ?: $airport->id;
            $selectedTitle = trim(($code ? $code . ' · ' : '') . $airport->name);
        }
    }

    $airportSearchUrl = route('flight.airport.search', [], false);
?>

<div class="form-group tsa-airport-picker">
    <i class="field-icon icofont-paper-plane"></i>
    <div class="form-content">
        <label>{{ $title ?? '' }}</label>

        <input type="text"
               class="form-control font-size-14 tsa-airport-input"
               placeholder="{{ $placeholder ?? __('City or airport') }}"
               value="{{ $selectedTitle }}"
               autocomplete="off"
               spellcheck="false"
               autocorrect="off"
               autocapitalize="characters"
               data-airport-url="{{ $airportSearchUrl }}">

        <input type="hidden"
               class="tsa-airport-value"
               name="{{ $inputName }}"
               value="{{ $selected }}">

        <div class="tsa-airport-dropdown"></div>
    </div>
</div>

<style>
    .tsa-airport-picker .form-content {
        position: relative;
    }

    .tsa-airport-input {
        border: 0 !important;
        padding-left: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        color: #152b23 !important;
        cursor: text !important;
    }

    .tsa-airport-dropdown {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        min-width: 380px;
        width: min(460px, calc(100vw - 32px));
        max-height: 340px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #dbe7e1;
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(15,50,34,.16);
        z-index: 99999;
        margin-top: 10px;
    }

    .tsa-airport-item,
    .tsa-airport-empty {
        padding: 12px 14px;
        font-size: 14px;
        line-height: 1.4;
        color: #1a2b48;
    }

    .tsa-airport-item {
        cursor: pointer;
    }

    .tsa-airport-item:hover {
        background: #f4faf7;
    }

    .tsa-airport-code {
        font-weight: 700;
        color: #0f766e;
        margin-right: 5px;
    }

    .tsa-airport-desc {
        display: block;
        font-size: 12px;
        color: #6c757d;
        margin-top: 2px;
    }
</style>

<script>
(function () {
    if (window.__tsaAirportPickerLoaded) return;
    window.__tsaAirportPickerLoaded = true;

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, delay || 250);
        };
    }

    function getItems(payload) {
        if (!payload) return [];
        var items = payload.data || payload.results || [];
        if (!Array.isArray(items)) {
            items = Object.values(items);
        }
        return items;
    }

    function escapeAirportHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
        });
    }

    function render(input, items) {
        var picker = input.closest('.tsa-airport-picker');
        var dropdown = picker.querySelector('.tsa-airport-dropdown');

        dropdown.innerHTML = '';

        if (!items.length) {
            dropdown.innerHTML = '<div class="tsa-airport-empty">Havalimanı bulunamadı</div>';
            dropdown.style.display = 'block';
            return;
        }

        items.forEach(function (item) {
            var code = String(item.code || item.id || '').toUpperCase();
            var title = item.name || item.title || item.text || code;
            var desc = item.address || item.desc || item.country || '';
            var cleanTitle = String(title).replace(code + ' - ', '');
            var display = code ? code + ' · ' + cleanTitle : cleanTitle;

            var row = document.createElement('div');
            row.className = 'tsa-airport-item';
            row.innerHTML =
                '<span class="tsa-airport-code">' + escapeAirportHtml(code) + '</span>' +
                '<span>' + escapeAirportHtml(cleanTitle) + '</span>' +
                (desc ? '<span class="tsa-airport-desc">' + escapeAirportHtml(desc) + '</span>' : '');

            row.addEventListener('mousedown', function (e) {
                e.preventDefault();

                input.value = display;
                picker.querySelector('.tsa-airport-value').value = code;
                dropdown.style.display = 'none';
            });

            dropdown.appendChild(row);
        });

        dropdown.style.display = 'block';
    }

    function fetchAirports(input, q, clearHidden) {
        var picker = input.closest('.tsa-airport-picker');
        var dropdown = picker.querySelector('.tsa-airport-dropdown');
        var hidden = picker.querySelector('.tsa-airport-value');

        if (clearHidden) {
            hidden.value = '';
        }

        dropdown.innerHTML = '<div class="tsa-airport-empty">Havalimanları aranıyor...</div>';
        dropdown.style.display = 'block';

        var url = input.getAttribute('data-airport-url') + '?search=' + encodeURIComponent(q || '') + '&_=' + Date.now();

        fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (payload) {
            render(input, getItems(payload));
        })
        .catch(function () {
            dropdown.innerHTML = '<div class="tsa-airport-empty">Havalimanı araması yüklenemedi</div>';
            dropdown.style.display = 'block';
        });
    }

    var doSearch = debounce(function (input) {
        fetchAirports(input, input.value.trim(), true);
    }, 200);

    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('tsa-airport-input')) return;
        doSearch(e.target);
    });

    document.addEventListener('focusin', function (e) {
        if (!e.target.classList.contains('tsa-airport-input')) return;
        e.target.select();

        // Boş tıklamada da liste açılsın.
        // Mevcut seçimi bozmadan popüler/mevcut havalimanlarını getir.
        fetchAirports(e.target, '', false);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('tsa-airport-input')) return;
        fetchAirports(e.target, '', false);
    });

    document.addEventListener('click', function (e) {
        document.querySelectorAll('.tsa-airport-dropdown').forEach(function (dropdown) {
            if (!dropdown.closest('.tsa-airport-picker').contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });
})();
</script>
