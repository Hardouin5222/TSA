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
            $selectedTitle = trim(($code ? $code . ' - ' : '') . $airport->name);
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
        color: #5191fa !important;
        cursor: text !important;
    }

    .tsa-airport-dropdown {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        width: 260px;
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 8px 20px rgba(0,0,0,.12);
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
        background: #f5f8ff;
    }

    .tsa-airport-code {
        font-weight: 700;
        color: #5191fa;
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

    function render(input, items) {
        var picker = input.closest('.tsa-airport-picker');
        var dropdown = picker.querySelector('.tsa-airport-dropdown');

        dropdown.innerHTML = '';

        if (!items.length) {
            dropdown.innerHTML = '<div class="tsa-airport-empty">Airport not found</div>';
            dropdown.style.display = 'block';
            return;
        }

        items.forEach(function (item) {
            var code = item.code || item.id || '';
            var title = item.title || item.text || item.name || code;
            var desc = item.desc || item.address || item.country || '';

            var row = document.createElement('div');
            row.className = 'tsa-airport-item';
            row.innerHTML =
                '<span class="tsa-airport-code">' + String(code).toUpperCase() + '</span>' +
                '<span>' + title.replace(String(code).toUpperCase() + ' - ', '') + '</span>' +
                (desc ? '<span class="tsa-airport-desc">' + desc + '</span>' : '');

            row.addEventListener('mousedown', function (e) {
                e.preventDefault();

                input.value = title;
                picker.querySelector('.tsa-airport-value').value = String(code).toUpperCase();
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

        dropdown.innerHTML = '<div class="tsa-airport-empty">Loading...</div>';
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
            dropdown.innerHTML = '<div class="tsa-airport-empty">Airport search error</div>';
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
