@extends('layouts.app')

@php
    $offers = collect($offers ?? $rows ?? []);
    $rows = $offers;

    $criteria = array_merge([
        'origin' => '',
        'destination' => '',
        'departure_date' => now()->format('Y-m-d'),
        'return_date' => null,
        'adult_count' => 1,
        'child_count' => 0,
        'passenger_total' => 1,
        'sort' => 'recommended',
        'airlines' => [],
        'selected_offer' => null,
        'selected_fare' => null,
    ], $criteria ?? []);

    $criteria['passenger_total'] = (int)($criteria['adult_count'] ?? 1) + (int)($criteria['child_count'] ?? 0);

    $routeName = route('flight.search');
    $selectedOffer = $selectedOffer ?? null;
    $selectedFare = $selectedFare ?? null;
    $selectedOfferData = $selectedOfferData ?? $selectedOffer ?? null;
    $airlineFilters = $airlineFilters ?? [];

    $supportCard = $supportCard ?? [
        'title' => __('Need help?'),
        'body' => __('Our support team can help you complete your booking.'),
    ];

    $queryState = [
        'origin' => $criteria['origin'] ?? '',
        'destination' => $criteria['destination'] ?? '',
        'departure_date' => $criteria['departure_date'] ?? now()->format('Y-m-d'),
        'return_date' => $criteria['return_date'] ?? '',
        'adult_count' => $criteria['adult_count'] ?? 1,
        'child_count' => $criteria['child_count'] ?? 0,
        'sort' => $criteria['sort'] ?? 'recommended',
    ];
@endphp

@push('css')
    <style>
        .tsa-flight-shell{padding:24px 0 56px;background:linear-gradient(135deg,#fff7ef 0%,#f7f4ea 52%,#edf6f1 100%)}
        .tsa-flight-container{max-width:1480px;margin:0 auto;padding:0 24px}
        .tsa-flight-breadcrumb{display:flex;gap:10px;align-items:center;font-size:15px;font-weight:600;color:#0f766e;margin-bottom:18px}
        .tsa-flight-panel{background:#fff;border:1px solid #d9e7e0;border-radius:28px;box-shadow:0 20px 50px rgba(15,50,34,.08)}
        .tsa-topbar{display:flex;justify-content:space-between;align-items:center;padding:24px 28px;margin-bottom:18px}
        .tsa-brand{display:flex;gap:16px;align-items:center}
        .tsa-brand-mark{width:48px;height:48px;border-radius:16px;background:#0f766e;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700}
        .tsa-brand-copy strong{display:block;font-size:18px;color:#182a25}
        .tsa-brand-copy span{display:block;font-size:14px;color:#5b6b65}
        .tsa-topnav{display:flex;gap:28px;align-items:center}
        .tsa-topnav a{font-size:15px;font-weight:600;color:#31443d;text-decoration:none}
        .tsa-login-chip{padding:14px 22px;border-radius:18px;border:1px solid #d4dfda;background:#fff}
        .tsa-search-strip{display:grid;grid-template-columns:1.2fr .9fr .9fr .9fr .7fr .7fr;gap:16px;padding:22px 28px;margin-bottom:18px}
        .tsa-search-field{padding:18px 18px 16px;border:1px solid #dbe7e1;border-radius:20px;background:#fbfcfb}
        .tsa-search-field label{display:block;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7a73;margin-bottom:8px}
        .tsa-search-field input,.tsa-search-field select{width:100%;border:0;background:transparent;font-size:24px;font-weight:700;color:#192c26;outline:none}
        .tsa-search-field small{display:block;margin-top:8px;font-size:14px;color:#71817a}
        .tsa-search-submit{border:0;border-radius:18px;background:#0f766e;color:#fff;font-weight:700;font-size:18px;padding:0 20px;cursor:pointer}
        .tsa-layout{display:grid;grid-template-columns:300px minmax(0,1fr) 250px;gap:18px;align-items:start}
        .tsa-sidebar,.tsa-support-card,.tsa-result-card,.tsa-day-tabs,.tsa-sort-tabs,.tsa-empty-card,.tsa-summary-card,.tsa-modal-card,.tsa-modal-shell{background:#fff;border:1px solid #d9e7e0;border-radius:24px;box-shadow:0 20px 45px rgba(15,50,34,.07)}
        .tsa-sidebar{padding:22px}
        .tsa-rail-icons{display:flex;flex-direction:column;gap:14px;margin-bottom:18px}
        .tsa-rail-icon{width:44px;height:44px;border-radius:16px;background:#f4f7f5;border:1px solid #e3ece7;display:flex;align-items:center;justify-content:center;color:#0f766e;font-size:18px}
        .tsa-filter-card{padding:18px;border:1px solid #e2ece7;border-radius:22px;background:#fbfcfb;margin-bottom:18px}
        .tsa-filter-card h3{margin:0 0 8px;font-size:14px;font-weight:800;color:#1f352d;text-transform:uppercase;letter-spacing:.04em}
        .tsa-filter-card p{margin:0;font-size:15px;color:#60716b;line-height:1.45}
        .tsa-toggle{display:flex;justify-content:space-between;align-items:center}
        .tsa-switch{width:44px;height:28px;border-radius:999px;background:#e0e8e3;position:relative}
        .tsa-switch::after{content:"";position:absolute;top:4px;left:4px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 3px 8px rgba(0,0,0,.12)}
        .tsa-filter-group{margin-top:20px}
        .tsa-filter-group h4{margin:0 0 12px;font-size:14px;font-weight:800;color:#1f352d}
        .tsa-filter-option{display:flex;align-items:center;gap:10px;margin-bottom:12px;font-size:16px;color:#33463f}
        .tsa-filter-option input{width:22px;height:22px}
        .tsa-results-col{display:flex;flex-direction:column;gap:18px}
        .tsa-search-summary{padding:22px 24px}
        .tsa-route{display:flex;gap:16px;align-items:center;justify-content:space-between;font-size:18px;font-weight:700;color:#1d332b}
        .tsa-route-meta{display:flex;gap:20px;align-items:center;color:#5d6d66;font-size:14px;font-weight:600}
        .tsa-edit-links{display:flex;gap:26px;font-weight:700;color:#335249}
        .tsa-day-tabs,.tsa-sort-tabs{display:grid;overflow:hidden}
        .tsa-day-tabs{grid-template-columns:repeat(3,1fr)}
        .tsa-sort-tabs{grid-template-columns:repeat(4,1fr)}
        .tsa-day-tab,.tsa-sort-tab{padding:18px 22px;text-align:center;font-size:18px;font-weight:700;border-right:1px solid #e1ebe6}
        .tsa-day-tab:last-child,.tsa-sort-tab:last-child{border-right:0}
        .tsa-day-tab.is-active,.tsa-sort-tab.is-active{background:#0f766e;color:#fff}
        .tsa-result-card{display:grid;grid-template-columns:minmax(0,1fr) 220px;overflow:hidden}
        .tsa-result-main{padding:24px 26px}
        .tsa-card-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
        .tsa-badge{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:#f0f7f4;color:#0f766e;font-size:13px;font-weight:800}
        .tsa-airline-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
        .tsa-airline-name{font-size:20px;font-weight:800;color:#162b24;margin:0}
        .tsa-airline-meta{margin-top:4px;font-size:17px;color:#60716b}
        .tsa-price-rail{font-size:20px;font-weight:800;color:#0f766e}
        .tsa-timeline{display:grid;grid-template-columns:1fr 1fr 1fr;align-items:center;padding:18px 0;border-top:1px solid #ebf0ed;border-bottom:1px solid #ebf0ed}
        .tsa-time-block strong{display:block;font-size:22px;color:#162b24}
        .tsa-time-block span{font-size:14px;color:#677771}
        .tsa-timeline-center{text-align:center;color:#50615a}
        .tsa-timeline-center strong{display:block;font-size:22px;color:#243730}
        .tsa-timeline-line{height:1px;background:linear-gradient(90deg,transparent,#b8c8c1,transparent);margin:10px 26px}
        .tsa-card-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:18px}
        .tsa-pill-row{display:flex;gap:10px;flex-wrap:wrap}
        .tsa-pill{padding:8px 14px;border-radius:999px;background:#f3f6f4;color:#475a53;font-size:14px;font-weight:700}
        .tsa-card-actions{display:flex;gap:10px}
        .tsa-primary-btn,.tsa-secondary-btn,.tsa-modal-submit{display:inline-flex;align-items:center;justify-content:center;border-radius:18px;font-weight:800;text-decoration:none;border:1px solid #d8e6df;padding:14px 20px;cursor:pointer}
        .tsa-primary-btn,.tsa-modal-submit{background:#0f766e;color:#fff;border-color:#0f766e}
        .tsa-secondary-btn{background:#fff;color:#22362f}
        .tsa-result-aside{border-left:1px solid #e5ede8;padding:24px 22px;display:flex;flex-direction:column;justify-content:center;gap:18px}
        .tsa-result-aside .price{font-size:44px;font-weight:900;color:#10251f;text-align:center}
        .tsa-result-aside .currency{font-size:16px;color:#66766f;text-align:center}
        .tsa-result-aside .tsa-primary-btn{font-size:28px;padding:18px 24px;border-radius:20px}
        .tsa-support-card{padding:22px;position:sticky;top:22px}
        .tsa-support-card .icon{width:72px;height:72px;border-radius:22px;background:#eef7f3;color:#0f766e;display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 18px}
        .tsa-support-card h3{margin:0 0 10px;font-size:18px;text-align:center;color:#10251f}
        .tsa-support-card p{margin:0;text-align:center;font-size:16px;line-height:1.6;color:#5f716a}
        .tsa-summary-card{padding:22px;position:sticky;top:22px}
        .tsa-summary-card h3{margin:0 0 14px;font-size:34px;line-height:1.05;color:#162b24}
        .tsa-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:18px 0}
        .tsa-summary-box{padding:16px;border:1px solid #e2ebe6;border-radius:20px;background:#fbfcfb}
        .tsa-summary-box label{display:block;font-size:13px;color:#6b7a73;margin-bottom:8px}
        .tsa-summary-box strong{display:block;font-size:18px;color:#1a2f28}
        .tsa-summary-body{font-size:16px;line-height:1.6;color:#5e7069}
        .tsa-summary-actions{display:flex;flex-direction:column;gap:12px;margin-top:18px}
        .tsa-empty-card{padding:40px 28px;text-align:center}
        .tsa-empty-card h2{margin:0 0 12px;font-size:34px;color:#162b24}
        .tsa-empty-card p{margin:0;color:#61736c;font-size:17px}
        .tsa-modal{position:fixed;inset:0;background:rgba(17,31,27,.55);display:none;align-items:center;justify-content:center;padding:24px;z-index:2000}
        .tsa-modal.is-open{display:flex}
        .tsa-modal-shell{width:min(1160px,100%);padding:24px 24px 18px}
        .tsa-modal-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}
        .tsa-modal-head h3{margin:0;font-size:28px;color:#162b24}
        .tsa-modal-close{width:44px;height:44px;border-radius:50%;border:0;background:#f1f4f2;font-size:28px;cursor:pointer}
        .tsa-modal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .tsa-modal-card{padding:18px;border:1px solid #dce7e2;cursor:pointer}
        .tsa-modal-card.is-selected{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.08)}
        .tsa-modal-card h4{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 18px;font-size:18px;color:#162b24}
        .tsa-modal-card .radio{width:14px;height:14px;border-radius:50%;border:1px solid #0f766e}
        .tsa-modal-card.is-selected .radio{background:#0f766e;box-shadow:0 0 0 4px rgba(15,118,110,.15)}
        .tsa-modal-section{margin-bottom:16px}
        .tsa-modal-section strong{display:block;font-size:14px;color:#1f352d;margin-bottom:10px}
        .tsa-modal-section ul{margin:0;padding:0;list-style:none}
        .tsa-modal-section li{margin-bottom:9px;font-size:15px;color:#566861}
        .tsa-modal-price{font-size:18px;font-weight:900;color:#162b24;margin-top:20px}
        .tsa-modal-delta{font-size:13px;font-weight:700;color:#6b7d76;margin-top:4px}
        .tsa-modal-footer{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:18px}
        .tsa-modal-selection{font-size:16px;color:#4f625b}
        .tsa-modal-selection strong{display:block;font-size:24px;color:#162b24}
        @media (max-width: 1280px){.tsa-layout{grid-template-columns:280px minmax(0,1fr)}.tsa-support-card{display:none}.tsa-search-strip{grid-template-columns:1fr 1fr 1fr 1fr 1fr auto}.tsa-result-card{grid-template-columns:1fr}.tsa-result-aside{border-left:0;border-top:1px solid #e5ede8}}
        @media (max-width: 991px){.tsa-topbar,.tsa-search-strip{grid-template-columns:1fr;display:grid}.tsa-layout{grid-template-columns:1fr}.tsa-modal-grid{grid-template-columns:1fr}.tsa-day-tabs,.tsa-sort-tabs{grid-template-columns:1fr 1fr}.tsa-summary-card{position:static}.tsa-sidebar{order:2}}
    </style>
@endpush

@section('content')
    <div class="tsa-flight-shell">
        <div class="tsa-flight-container">
        @if (session('error'))
            <div class="alert alert-danger" style="max-width:1480px;margin:0 auto 18px;">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="max-width:1480px;margin:0 auto 18px;">{{ session('success') }}</div>
        @endif

            <div class="tsa-flight-breadcrumb">
                <a href="{{ url('/') }}">{{ __('Ana sayfaya don') }}</a>
                <span>/</span>
                <span>{{ __('Ucus sonuclari') }}</span>
            </div>

            <div class="tsa-flight-panel tsa-topbar">
                <div class="tsa-brand">
                    <div class="tsa-brand-mark">✈</div>
                    <div class="tsa-brand-copy">
                        <strong>{{ config('app.name', 'Travel Super App') }}</strong>
                        <span>{{ __('Flights') }}</span>
                    </div>
                </div>
                <div class="tsa-topnav">
                    <a href="{{ url('/') }}">{{ __('Ana sayfa') }}</a>
                    <a href="{{ $routeName }}">{{ __('Ucuslar') }}</a>
                    <a href="#support">{{ __('Destek') }}</a>
                    <a href="{{ url('/login') }}" class="tsa-login-chip">{{ __('Giris yap') }}</a>
                </div>
            </div>

            <form class="tsa-flight-panel tsa-search-strip" method="GET" action="{{ $routeName }}">
                <div class="tsa-search-field">
                    <label>{{ __('Nereden') }}</label>
                    <input type="text" name="origin" value="{{ $criteria['origin'] }}" maxlength="3">
                    <small>{{ __('IATA kodu veya sehir') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ __('Nereye') }}</label>
                    <input type="text" name="destination" value="{{ $criteria['destination'] }}" maxlength="3">
                    <small>{{ __('Destinasyon secimi') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ __('Gidis') }}</label>
                    <input type="date" name="departure_date" value="{{ $criteria['departure_date'] }}">
                    <small>{{ __('Satin alma niyeti yuksek tarih') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ __('Donus') }}</label>
                    <input type="date" name="return_date" value="{{ $criteria['return_date'] }}">
                    <small>{{ __('Paket eslestirmesi icin onemli') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ __('Yolcu') }}</label>
                    <select name="adult_count">
                        @for ($adult = 1; $adult <= 6; $adult++)
                            <option value="{{ $adult }}" @selected($criteria['adult_count'] == $adult)>{{ $adult }} {{ __('yetiskin') }}</option>
                        @endfor
                    </select>
                    <small>{{ __('3 tik akisi: ara, sec, satin al.') }}</small>
                </div>
                <button class="tsa-search-submit" type="submit">{{ __('Ucuslari goster') }}</button>
            </form>

            @if ($offers->isEmpty())
                <div class="tsa-empty-card">
                    <h2>{{ __('Bu rota icin teklif bulunamadi') }}</h2>
                    <p>{{ __('Origin, destination veya supplier katalog eslesmesini kontrol edip tekrar deneyelim.') }}</p>
                </div>
            @else
                <div class="tsa-layout">
                    <aside class="tsa-sidebar">
                        <div class="tsa-rail-icons">
                            <div class="tsa-rail-icon">✈</div>
                            <div class="tsa-rail-icon">⇄</div>
                            <div class="tsa-rail-icon">🚗</div>
                            <div class="tsa-rail-icon">🧾</div>
                        </div>

                        <div class="tsa-filter-card">
                            <div class="tsa-toggle">
                                <div>
                                    <h3>{{ __('Fiyat alarmi kur') }}</h3>
                                    <p>{{ __('Fiyat degisirse haber verelim') }}</p>
                                </div>
                                <div class="tsa-switch"></div>
                            </div>
                        </div>

                        <form method="GET" action="{{ $routeName }}">
                            @foreach ($queryState as $key => $value)
                                @if ($key !== 'sort')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div class="tsa-filter-group">
                                <h4>{{ __('Oneriler') }}</h4>
                                <label class="tsa-filter-option">
                                    <input type="checkbox" name="direct_only" value="1" @checked($criteria['direct_only']) onchange="this.form.submit()">
                                    <span>{{ __('Direkt') }}</span>
                                </label>
                            </div>

                            <div class="tsa-filter-group">
                                <h4>{{ __('Hava yolu sirketleri') }}</h4>
                                @foreach ($airlineFilters as $airlineFilter)
                                    <label class="tsa-filter-option">
                                        <input type="checkbox" name="airlines[]" value="{{ $airlineFilter['code'] }}" @checked(in_array($airlineFilter['code'], $criteria['airlines'], true)) onchange="this.form.submit()">
                                        <span>{{ $airlineFilter['name'] }} ({{ $airlineFilter['count'] }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </form>
                    </aside>

                    <section class="tsa-results-col">
                        <div class="tsa-flight-panel tsa-search-summary">
                            <div class="tsa-route">
                                <div>{{ ($criteria['origin'] ?: '---') . ' → ' . ($criteria['destination'] ?: '---') }}</div>
                                <div class="tsa-edit-links">
                                    <span>{{ __('Aramayi duzenle') }}</span>
                                    <span>{{ __('Gunluk fiyatlar') }}</span>
                                </div>
                            </div>
                            <div class="tsa-route-meta">
                                <span>{{ \Carbon\Carbon::parse($criteria['departure_date'])->translatedFormat('d M D') }}</span>
                                <span>{{ __(':count Yolcu', ['count' => $criteria['passenger_total']]) }}</span>
                                <span>{{ __(':count teklif', ['count' => $offers->count()]) }}</span>
                            </div>
                        </div>

                        <div class="tsa-day-tabs">
                            <div class="tsa-day-tab">{{ \Carbon\Carbon::parse($criteria['departure_date'])->copy()->subDay()->translatedFormat('d M D') }}</div>
                            <div class="tsa-day-tab is-active">{{ \Carbon\Carbon::parse($criteria['departure_date'])->translatedFormat('d M D') }}</div>
                            <div class="tsa-day-tab">{{ \Carbon\Carbon::parse($criteria['departure_date'])->copy()->addDay()->translatedFormat('d M D') }}</div>
                        </div>

                        <div class="tsa-sort-tabs">
                            @foreach (['recommended' => __('En ucuz'), 'duration' => __('En hizli'), 'departure' => __('Once aktarmasiz'), 'price' => __('Onerilen')] as $sortKey => $sortLabel)
                                <form method="GET" action="{{ $routeName }}">
                                    @foreach ($queryState as $key => $value)
                                        @if ($key !== 'sort')
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    @if ($criteria['direct_only'])
                                        <input type="hidden" name="direct_only" value="1">
                                    @endif
                                    @foreach ($criteria['airlines'] as $selectedAirline)
                                        <input type="hidden" name="airlines[]" value="{{ $selectedAirline }}">
                                    @endforeach
                                    <input type="hidden" name="sort" value="{{ $sortKey }}">
                                    <button class="tsa-sort-tab {{ $criteria['sort'] === $sortKey ? 'is-active' : '' }}" type="submit">{{ $sortLabel }}</button>
                                </form>
                            @endforeach
                        </div>

                        @foreach ($offers as $offer)
                            <article class="tsa-result-card" id="offer-{{ $offer['id'] }}">
                                <div class="tsa-result-main">
                                    <div class="tsa-card-badges">
                                        @foreach ($offer['badges'] as $badge)
                                            <span class="tsa-badge">{{ $badge }}</span>
                                        @endforeach
                                    </div>

                                    <div class="tsa-airline-row">
                                        <div>
                                            <h2 class="tsa-airline-name">{{ $offer['airline_name'] }}</h2>
                                            <div class="tsa-airline-meta">{{ $offer['provider'] }} • {{ $offer['selected_fare']['label'] }}</div>
                                        </div>
                                        <div class="tsa-price-rail">{{ $offer['display_price'] }}</div>
                                    </div>

                                    <div class="tsa-timeline">
                                        <div class="tsa-time-block">
                                            <strong>{{ $offer['departure_time_label'] }}</strong>
                                            <span>{{ $offer['origin'] }}</span>
                                        </div>
                                        <div class="tsa-timeline-center">
                                            <strong>{{ $offer['duration_label'] }}</strong>
                                            <div class="tsa-timeline-line"></div>
                                            <span>{{ $offer['stop_label'] }}</span>
                                        </div>
                                        <div class="tsa-time-block" style="text-align:right">
                                            <strong>{{ $offer['arrival_time_label'] }}</strong>
                                            <span>{{ $offer['destination'] }}</span>
                                        </div>
                                    </div>

                                    <div class="tsa-card-footer">
                                        <div class="tsa-pill-row">
                                            @if ($offer['display_checked_baggage'])
                                                <span class="tsa-pill">{{ $offer['display_checked_baggage'] }}</span>
                                            @endif
                                            @if ($offer['display_hand_baggage'])
                                                <span class="tsa-pill">{{ $offer['display_hand_baggage'] }}</span>
                                            @endif
                                            @foreach ($offer['display_features'] as $feature)
                                                <span class="tsa-pill">{{ $feature }}</span>
                                            @endforeach
                                        </div>
                                        <div class="tsa-card-actions">
                                            <button type="button" class="tsa-secondary-btn js-open-modal" data-modal-id="modal-{{ $offer['id'] }}">{{ __('Paketleri goster') }}</button>
                                            <button type="button" class="tsa-primary-btn js-open-modal" data-modal-id="modal-{{ $offer['id'] }}">{{ __('Sec') }}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tsa-result-aside">
                                    <div class="price">{{ $offer['display_price'] }}</div>
                                    <div class="currency">{{ $offer['selected_fare']['label'] }} • {{ $offer['fare_family'] }}</div>
                                    <button type="button" class="tsa-primary-btn js-open-modal" data-modal-id="modal-{{ $offer['id'] }}">{{ __('Sec') }}</button>
                                </div>
                            </article>

                            <div class="tsa-modal" id="modal-{{ $offer['id'] }}">
                                <div class="tsa-modal-shell">
                                    <div class="tsa-modal-head">
                                        <h3>{{ __('Gidis paketini secin') }}</h3>
                                        <button type="button" class="tsa-modal-close js-close-modal">×</button>
                                    </div>
                                    <div class="tsa-modal-grid">
                                        @foreach ($offer['fare_options'] as $fareOption)
                                            <div class="tsa-modal-card {{ $fareOption['is_selected'] ? 'is-selected' : '' }}">
                                                <h4>
                                                    <span>{{ $fareOption['label'] }}</span>
                                                    <span class="radio"></span>
                                                </h4>
                                                <div class="tsa-modal-section">
                                                    <strong>{{ __('Bagaj') }}</strong>
                                                    <ul>
                                                        @if ($fareOption['hand_baggage'])
                                                            <li>{{ $fareOption['hand_baggage'] }}</li>
                                                        @endif
                                                        @if ($fareOption['checked_baggage'])
                                                            <li>{{ $fareOption['checked_baggage'] }}</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                                <div class="tsa-modal-section">
                                                    <strong>{{ __('Diger') }}</strong>
                                                    <ul>
                                                        @forelse ($fareOption['features'] as $feature)
                                                            <li>{{ $feature }}</li>
                                                        @empty
                                                            <li>{{ __('Temel paket') }}</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                                @php
                                                    $fareTotalLabel = $fareOption['total_price_label'] ?? $offer['display_price'];
                                                    $fareDeltaLabel = $fareOption['delta_label'] ?? null;
                                                    $zeroDeltaLabels = ['$0', '$0.00', '€0', '€0.00', '₺0', '₺0.00', '0'];
                                                @endphp
                                                <div class="tsa-modal-price">{{ $fareTotalLabel }}</div>
                                                @if(!empty($fareDeltaLabel) && !in_array($fareDeltaLabel, $zeroDeltaLabels, true))
                                                    <div class="tsa-modal-delta">{{ __('Fiyat farki') }}: {{ $fareDeltaLabel }}</div>
                                                @endif
                                                <form method="POST" action="{{ route('flight.supplier.quote') }}" style="margin-top:18px">
                                                    @csrf
                                                    @foreach ($queryState as $key => $value)
                                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                    @endforeach
                                                    @if ($criteria['direct_only'])
                                                        <input type="hidden" name="direct_only" value="1">
                                                    @endif
                                                    @foreach ($criteria['airlines'] as $selectedAirline)
                                                        <input type="hidden" name="airlines[]" value="{{ $selectedAirline }}">
                                                    @endforeach
                                                    <input type="hidden" name="selected_offer" value="{{ $offer['id'] }}">
                                                    <input type="hidden" name="selected_fare" value="{{ $fareOption['id'] }}">
                                                    <button type="submit" class="tsa-modal-submit" style="width:100%">{{ __('Fiyati dogrula ve checkout’a ilerle') }}</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="tsa-modal-footer">
                                        <div class="tsa-modal-selection">
                                            <strong>{{ $offer['airline_name'] }}</strong>
                                            <span>{{ $offer['selected_fare']['label'] }} • {{ $offer['display_price'] }}</span>
                                        </div>
                                        <button type="button" class="tsa-secondary-btn js-close-modal">{{ __('Kapat') }}</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>

                    <aside class="tsa-support-card" id="support">
                        <div class="icon">◎</div>
                        <h3>{{ $supportCard['title'] }}</h3>
                        <p>{{ $supportCard['body'] }}</p>
                    </aside>
                </div>

                @if ($selectedOfferData)
                    <div class="tsa-summary-card" style="margin-top:18px">
                        <span class="tsa-badge">{{ __('Secili teklif') }}</span>
                        <h3>{{ __('Karar ozeti') }}</h3>
                        <div class="tsa-summary-body">
                            <strong>{{ $selectedOfferData['airline_name'] }}</strong>
                            <div>{{ $selectedOfferData['route_label'] }}</div>
                        </div>
                        <div class="tsa-summary-grid">
                            <div class="tsa-summary-box">
                                <label>{{ __('Fiyat') }}</label>
                                <strong>{{ $selectedOfferData['display_price'] }}</strong>
                            </div>
                            <div class="tsa-summary-box">
                                <label>{{ __('Sure') }}</label>
                                <strong>{{ $selectedOfferData['duration_label'] }}</strong>
                            </div>
                            <div class="tsa-summary-box">
                                <label>{{ __('Paket') }}</label>
                                <strong>{{ $selectedOfferData['selected_fare']['label'] }}</strong>
                            </div>
                            <div class="tsa-summary-box">
                                <label>{{ __('Bagaj') }}</label>
                                <strong>{{ $selectedOfferData['display_checked_baggage'] ?? __('Bilgi bekleniyor') }}</strong>
                            </div>
                        </div>
                        <div class="tsa-summary-body">
                            {{ __('Bu panel satin alma kararini destekler. Sonraki adimda secilen supplier paketini checkout koprusune baglayacagiz.') }}
                        </div>
                        <div class="tsa-summary-actions">
                            <a href="#offer-{{ $selectedOfferData['id'] }}" class="tsa-primary-btn">{{ __('Paketi secildi olarak goster') }}</a>
                            <a href="#support" class="tsa-secondary-btn">{{ __('Destek notu') }}</a>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.querySelectorAll('.js-open-modal').forEach(function(button) {
            button.addEventListener('click', function() {
                var modal = document.getElementById(button.dataset.modalId);
                if (modal) modal.classList.add('is-open');
            });
        });
        document.querySelectorAll('.js-close-modal').forEach(function(button) {
            button.addEventListener('click', function() {
                button.closest('.tsa-modal').classList.remove('is-open');
            });
        });
        document.querySelectorAll('.tsa-modal').forEach(function(modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('is-open');
                }
            });
        });
    </script>
@endpush
