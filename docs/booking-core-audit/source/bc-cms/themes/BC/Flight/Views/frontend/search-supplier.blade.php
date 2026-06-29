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
        'direct_only' => false,
    ], $criteria ?? []);

    $criteria['passenger_total'] = (int)($criteria['adult_count'] ?? 1) + (int)($criteria['child_count'] ?? 0);

    $criteria['direct_only'] = filter_var($criteria['direct_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $criteria['airlines'] = is_array($criteria['airlines'] ?? [])
        ? $criteria['airlines']
        : array_filter([(string) ($criteria['airlines'] ?? '')]);
    $criteria['airlines'] = array_values(array_filter($criteria['airlines'], function ($airline) {
        return $airline !== null && $airline !== '';
    }));


    $tsaLocale = app()->getLocale();
    $tsaLocale = in_array($tsaLocale, ['tr', 'en', 'ru', 'ar'], true) ? $tsaLocale : 'en';

    $tsaFlightText = [
        'tr' => [
            'breadcrumb_home' => 'Ana sayfaya dön',
            'breadcrumb_results' => 'Uçuş sonuçları',
            'sandbox_badge' => 'Test ortamı',
            'sandbox_message' => 'Bu ekrandaki uçuş ve fiyat gerçek satışa açık canlı veri değildir. Supplier sandbox test cevabıdır.',
            'sandbox_support_title' => 'Test ortamı aktif',
            'sandbox_support_body' => 'Bu ekrandaki uçuş ve fiyatlar canlı satış verisi değildir. Gerçek fiyat için canlı supplier bağlantısı gerekir.',
            'from' => 'Nereden',
            'to' => 'Nereye',
            'departure' => 'Gidiş',
            'return' => 'Dönüş',
            'passenger' => 'Yolcu',
            'search_flights' => 'Uçuş ara',
            'departure_date' => 'Gidiş tarihi',
            'optional_return_date' => 'Opsiyonel dönüş tarihi',
            'passenger_count' => 'Yolcu sayısı',
            'example_from' => 'Örnek: İstanbul, IST, Sabiha',
            'example_to' => 'Örnek: London, LHR, JFK',
            'airport_placeholder' => 'Şehir veya havaalanı yazın',
            'empty_title' => 'Bu rota için uygun uçuş bulunamadı',
            'empty_body' => 'Havalimanı, tarih veya yolcu bilgisini değiştirerek tekrar arayın.',
            'price_alert_title' => 'FİYAT ALARMI KUR',
            'price_alert_body' => 'Fiyat değişirse haber verelim',
            'recommendations' => 'Öneriler',
            'airlines' => 'Hava yolu şirketleri',
            'direct' => 'Direkt',
            'direct_flight' => 'Direkt Uçuş',
            'edit_search' => 'Aramayı değiştir',
            'compare_dates' => 'Tarihleri karşılaştır',
            'recommended' => 'Önerilen',
            'cheapest' => 'En ucuz',
            'fastest' => 'En hızlı',
            'direct_first' => 'Direkt önce',
            'standard' => 'Standart',
            'basic' => 'Ekonomik',
            'flex' => 'Esnek',
            'premium' => 'Premium',
            'test_price' => 'Test fiyatı',
            'live_price' => 'Canlı fiyat',
            'departure_short' => 'Kalkış',
            'arrival_short' => 'Varış',
            'sandbox_availability' => 'Sandbox müsaitlik',
            'live_availability' => 'Canlı müsaitlik',
            'price_confirm_before_payment' => 'Ödeme öncesi fiyat doğrulama',
            'package_details' => 'Paket detayları',
            'select_continue' => 'Seç ve devam et',
            'choose_package' => 'Uçuş paketini seçin',
            'package_features' => 'Paket özellikleri',
            'price_difference' => 'Fiyat farkı',
            'continue_package' => 'Bu paketle devam et',
            'close' => 'Kapat',
            'test_not_for_sale' => 'Test fiyatıdır, satışa açık değildir',
            'test_label' => 'test',
            'adult_one' => ':count yetişkin',
            'adult_many' => ':count yetişkin',
            'passenger_one' => ':count Yolcu',
            'passenger_many' => ':count Yolcu',
            'offer_one' => ':count teklif',
            'offer_many' => ':count teklif',
            'stop_one' => ':count aktarma',
            'stop_many' => ':count aktarma',
            'selected_flight' => 'Seçili uçuş',
            'selected_summary' => 'Seçili uçuş özeti',
            'price' => 'Fiyat',
            'package' => 'Paket',
            'back_selected' => 'Seçili uçuşa dön',
            'nav_home' => 'Ana sayfa',
            'nav_flights' => 'Uçuşlar',
            'nav_support' => 'Destek',
            'nav_login' => 'Giriş yap',
        ],
        'en' => [
            'breadcrumb_home' => 'Back to homepage',
            'breadcrumb_results' => 'Flight results',
            'sandbox_badge' => 'Test mode',
            'sandbox_message' => 'The flight and price shown here are not live sale data. This is a supplier sandbox test response.',
            'sandbox_support_title' => 'Test mode active',
            'sandbox_support_body' => 'Flights and prices shown here are not live sale data. A live supplier connection is required for real pricing.',
            'from' => 'From',
            'to' => 'To',
            'departure' => 'Departure',
            'return' => 'Return',
            'passenger' => 'Passenger',
            'search_flights' => 'Search flights',
            'departure_date' => 'Departure date',
            'optional_return_date' => 'Optional return date',
            'passenger_count' => 'Passenger count',
            'example_from' => 'Example: Istanbul, IST, Sabiha',
            'example_to' => 'Example: London, LHR, JFK',
            'airport_placeholder' => 'City or airport',
            'empty_title' => 'No suitable flights found for this route',
            'empty_body' => 'Try changing the airport, date, or passenger information.',
            'price_alert_title' => 'SET PRICE ALERT',
            'price_alert_body' => 'We will notify you if the price changes',
            'recommendations' => 'Recommendations',
            'airlines' => 'Airlines',
            'direct' => 'Direct',
            'direct_flight' => 'Direct flight',
            'edit_search' => 'Edit search',
            'compare_dates' => 'Compare dates',
            'recommended' => 'Recommended',
            'cheapest' => 'Cheapest',
            'fastest' => 'Fastest',
            'direct_first' => 'Direct first',
            'standard' => 'Standard',
            'basic' => 'Basic',
            'flex' => 'Flex',
            'premium' => 'Premium',
            'test_price' => 'Test price',
            'live_price' => 'Live price',
            'departure_short' => 'Departure',
            'arrival_short' => 'Arrival',
            'sandbox_availability' => 'Sandbox availability',
            'live_availability' => 'Live availability',
            'price_confirm_before_payment' => 'Price confirmation before payment',
            'package_details' => 'Package details',
            'select_continue' => 'Select and continue',
            'choose_package' => 'Choose flight package',
            'package_features' => 'Package features',
            'price_difference' => 'Price difference',
            'continue_package' => 'Continue with this package',
            'close' => 'Close',
            'test_not_for_sale' => 'Test price, not available for sale',
            'test_label' => 'test',
            'adult_one' => ':count adult',
            'adult_many' => ':count adults',
            'passenger_one' => ':count passenger',
            'passenger_many' => ':count passengers',
            'offer_one' => ':count offer',
            'offer_many' => ':count offers',
            'stop_one' => ':count stop',
            'stop_many' => ':count stops',
            'selected_flight' => 'Selected flight',
            'selected_summary' => 'Selected flight summary',
            'price' => 'Price',
            'package' => 'Package',
            'back_selected' => 'Back to selected flight',
            'nav_home' => 'Home',
            'nav_flights' => 'Flights',
            'nav_support' => 'Support',
            'nav_login' => 'Login',
        ],
        'ru' => [
            'breadcrumb_home' => 'На главную',
            'breadcrumb_results' => 'Результаты поиска',
            'sandbox_badge' => 'Тестовый режим',
            'sandbox_message' => 'Рейс и цена на этом экране не являются реальными данными для продажи. Это тестовый ответ sandbox-поставщика.',
            'sandbox_support_title' => 'Тестовый режим активен',
            'sandbox_support_body' => 'Рейсы и цены на этом экране не являются реальными данными продажи. Для реальных цен требуется live-подключение поставщика.',
            'from' => 'Откуда',
            'to' => 'Куда',
            'departure' => 'Вылет',
            'return' => 'Возврат',
            'passenger' => 'Пассажир',
            'search_flights' => 'Найти рейсы',
            'departure_date' => 'Дата вылета',
            'optional_return_date' => 'Дата возврата необязательно',
            'passenger_count' => 'Количество пассажиров',
            'example_from' => 'Например: Istanbul, IST, Sabiha',
            'example_to' => 'Например: London, LHR, JFK',
            'airport_placeholder' => 'Город или аэропорт',
            'empty_title' => 'Подходящие рейсы по этому маршруту не найдены',
            'empty_body' => 'Измените аэропорт, дату или данные пассажиров и попробуйте снова.',
            'price_alert_title' => 'УВЕДОМЛЕНИЕ О ЦЕНЕ',
            'price_alert_body' => 'Мы сообщим, если цена изменится',
            'recommendations' => 'Рекомендации',
            'airlines' => 'Авиакомпании',
            'direct' => 'Прямой',
            'direct_flight' => 'Прямой рейс',
            'edit_search' => 'Изменить поиск',
            'compare_dates' => 'Сравнить даты',
            'recommended' => 'Рекомендуемые',
            'cheapest' => 'Самые дешевые',
            'fastest' => 'Самые быстрые',
            'direct_first' => 'Сначала прямые',
            'standard' => 'Стандарт',
            'basic' => 'Базовый',
            'flex' => 'Гибкий',
            'premium' => 'Премиум',
            'test_price' => 'Тестовая цена',
            'live_price' => 'Актуальная цена',
            'departure_short' => 'Вылет',
            'arrival_short' => 'Прибытие',
            'sandbox_availability' => 'Тестовая доступность',
            'live_availability' => 'Актуальная доступность',
            'price_confirm_before_payment' => 'Проверка цены перед оплатой',
            'package_details' => 'Детали пакета',
            'select_continue' => 'Выбрать и продолжить',
            'choose_package' => 'Выберите пакет рейса',
            'package_features' => 'Особенности пакета',
            'price_difference' => 'Разница в цене',
            'continue_package' => 'Продолжить с этим пакетом',
            'close' => 'Закрыть',
            'test_not_for_sale' => 'Тестовая цена, недоступна для продажи',
            'test_label' => 'тест',
            'adult_one' => ':count взрослый',
            'adult_many' => ':count взрослых',
            'passenger_one' => ':count пассажир',
            'passenger_many' => ':count пассажиров',
            'offer_one' => ':count предложение',
            'offer_many' => ':count предложений',
            'stop_one' => ':count пересадка',
            'stop_many' => ':count пересадок',
            'selected_flight' => 'Выбранный рейс',
            'selected_summary' => 'Сводка выбранного рейса',
            'price' => 'Цена',
            'package' => 'Пакет',
            'back_selected' => 'Вернуться к выбранному рейсу',
            'nav_home' => 'Главная',
            'nav_flights' => 'Рейсы',
            'nav_support' => 'Поддержка',
            'nav_login' => 'Вход',
        ],
        'ar' => [
            'breadcrumb_home' => 'العودة إلى الصفحة الرئيسية',
            'breadcrumb_results' => 'نتائج الرحلات',
            'sandbox_badge' => 'وضع الاختبار',
            'sandbox_message' => 'الرحلة والسعر المعروضان هنا ليسا بيانات بيع حقيقية. هذا رد اختباري من بيئة sandbox الخاصة بالمورّد.',
            'sandbox_support_title' => 'وضع الاختبار مفعّل',
            'sandbox_support_body' => 'الرحلات والأسعار المعروضة هنا ليست بيانات بيع مباشرة. يلزم اتصال مباشر بالمورّد للحصول على السعر الحقيقي.',
            'from' => 'من',
            'to' => 'إلى',
            'departure' => 'المغادرة',
            'return' => 'العودة',
            'passenger' => 'المسافر',
            'search_flights' => 'ابحث عن الرحلات',
            'departure_date' => 'تاريخ المغادرة',
            'optional_return_date' => 'تاريخ العودة اختياري',
            'passenger_count' => 'عدد المسافرين',
            'example_from' => 'مثال: Istanbul, IST, Sabiha',
            'example_to' => 'مثال: London, LHR, JFK',
            'airport_placeholder' => 'المدينة أو المطار',
            'empty_title' => 'لم يتم العثور على رحلات مناسبة لهذا المسار',
            'empty_body' => 'غيّر المطار أو التاريخ أو بيانات المسافر وحاول مرة أخرى.',
            'price_alert_title' => 'تنبيه السعر',
            'price_alert_body' => 'سنخبرك إذا تغيّر السعر',
            'recommendations' => 'الاقتراحات',
            'airlines' => 'شركات الطيران',
            'direct' => 'مباشر',
            'direct_flight' => 'رحلة مباشرة',
            'edit_search' => 'تعديل البحث',
            'compare_dates' => 'مقارنة التواريخ',
            'recommended' => 'موصى به',
            'cheapest' => 'الأرخص',
            'fastest' => 'الأسرع',
            'direct_first' => 'المباشر أولاً',
            'standard' => 'قياسي',
            'basic' => 'أساسي',
            'flex' => 'مرن',
            'premium' => 'مميز',
            'test_price' => 'سعر اختباري',
            'live_price' => 'سعر مباشر',
            'departure_short' => 'المغادرة',
            'arrival_short' => 'الوصول',
            'sandbox_availability' => 'توفر اختباري',
            'live_availability' => 'توفر مباشر',
            'price_confirm_before_payment' => 'تأكيد السعر قبل الدفع',
            'package_details' => 'تفاصيل الباقة',
            'select_continue' => 'اختر وتابع',
            'choose_package' => 'اختر باقة الرحلة',
            'package_features' => 'ميزات الباقة',
            'price_difference' => 'فرق السعر',
            'continue_package' => 'تابع بهذه الباقة',
            'close' => 'إغلاق',
            'test_not_for_sale' => 'سعر اختباري، غير متاح للبيع',
            'test_label' => 'اختبار',
            'adult_one' => ':count بالغ',
            'adult_many' => ':count بالغين',
            'passenger_one' => ':count مسافر',
            'passenger_many' => ':count مسافرين',
            'offer_one' => ':count عرض',
            'offer_many' => ':count عروض',
            'stop_one' => ':count توقف',
            'stop_many' => ':count توقفات',
            'selected_flight' => 'الرحلة المختارة',
            'selected_summary' => 'ملخص الرحلة المختارة',
            'price' => 'السعر',
            'package' => 'الباقة',
            'back_selected' => 'العودة إلى الرحلة المختارة',
            'nav_home' => 'الرئيسية',
            'nav_flights' => 'الرحلات',
            'nav_support' => 'الدعم',
            'nav_login' => 'تسجيل الدخول',
        ],
    ];

    $ft = function ($key) use ($tsaFlightText, $tsaLocale) {
        return $tsaFlightText[$tsaLocale][$key] ?? $tsaFlightText['en'][$key] ?? $key;
    };

    $ftCount = function ($count, $oneKey, $manyKey) use ($ft) {
        $count = (int) $count;
        $template = $ft($count === 1 ? $oneKey : $manyKey);

        return str_replace(':count', (string) $count, $template);
    };


    $routeName = route('flight.search');
    $selectedOffer = $selectedOffer ?? null;
    $selectedFare = $selectedFare ?? null;
    $selectedOfferData = $selectedOfferData ?? $selectedOffer ?? null;
    $airlineFilters = $airlineFilters ?? [];

    $supportCard = $supportCard ?? [
        'title' => __('Need help?'),
        'body' => __('Our support team can help you complete your booking.'),
    ];

    $supplierModeForUi = strtoupper((string) config('flight.supplier_engine_mode', env('TSA_SUPPLIER_ENGINE_MODE', '')));
    $isSandboxSearch = str_contains($supplierModeForUi, 'SANDBOX')
        || $offers->contains(function ($offer) {
            $provider = strtoupper((string) ($offer['provider'] ?? $offer['supplier_code'] ?? $offer['supplier'] ?? ''));
            $airline = strtolower((string) ($offer['airline_name'] ?? ''));

            return str_contains($provider, 'SANDBOX') || str_contains($airline, 'duffel airways');
        });

    if ($isSandboxSearch) {
        $supportCard = [
            'title' => $ft('sandbox_support_title'),
            'body' => $ft('sandbox_support_body'),
        ];
    }

    $queryState = [
        'origin' => $criteria['origin'] ?? '',
        'destination' => $criteria['destination'] ?? '',
        'departure_date' => $criteria['departure_date'] ?? now()->format('Y-m-d'),
        'return_date' => $criteria['return_date'] ?? '',
        'adult_count' => $criteria['adult_count'] ?? 1,
        'child_count' => $criteria['child_count'] ?? 0,
        'sort' => $criteria['sort'] ?? 'recommended',
    ];

    $airportLabel = function ($code) {
        $code = strtoupper(trim((string) $code));

        if (!$code) {
            return '';
        }

        $airport = \Modules\Flight\Models\Airport::query()
            ->where('code', $code)
            ->first();

        if (!$airport) {
            return $code;
        }

        return trim($code . ' · ' . $airport->name);
    };

    $originDisplay = $airportLabel($criteria['origin'] ?? '');
    $destinationDisplay = $airportLabel($criteria['destination'] ?? '');
    $airportSearchUrl = route('flight.airport.search', [], false);

    $humanFare = function ($label) use ($ft) {
        $label = trim((string) $label);

        return match (strtolower($label)) {
            'standard', 'standart' => $ft('standard'),
            'basic' => $ft('basic'),
            'flex' => $ft('flex'),
            'premium' => $ft('premium'),
            default => $label ?: $ft('standard'),
        };
    };

    $humanFeature = function ($feature) use ($isSandboxSearch, $ft) {
        $feature = trim((string) $feature);
        $key = strtolower($feature);

        return match ($key) {
            'duffel live availability' => $isSandboxSearch ? $ft('sandbox_availability') : $ft('live_availability'),
            'quote required before payment' => $ft('price_confirm_before_payment'),
            'direct' => $ft('direct_flight'),
            default => $feature,
        };
    };


    $airportCountry = function ($code) {
        $code = strtoupper(trim((string) $code));

        if (!$code) {
            return null;
        }

        return \Modules\Flight\Models\Airport::query()
            ->where('code', $code)
            ->value('country');
    };

    $originCountry = strtoupper((string) $airportCountry($criteria['origin'] ?? ''));
    $destinationCountry = strtoupper((string) $airportCountry($criteria['destination'] ?? ''));
    $isTurkeyDomestic = $originCountry === 'TR' && $destinationCountry === 'TR';

    // Display-only conversion follows Booking Core currency switcher.
    // Supplier quote/payment amount and currency are not changed here.
    $uiCurrency = strtoupper((string) \App\Currency::getCurrent('currency_main', setting_item('currency_main', 'try')));

    $activeCurrencyRows = collect(\App\Currency::getActiveCurrency() ?: []);
    $currencyRateToMain = $activeCurrencyRows
        ->mapWithKeys(function ($row) {
            return [strtoupper((string) ($row['currency_main'] ?? '')) => (float) ($row['rate'] ?? 1)];
        })
        ->filter(fn ($rate, $code) => $code && $rate > 0)
        ->all();

    $currencyRateToMain[strtoupper((string) setting_item('currency_main', 'try'))] = 1.0;

    $uiMoney = function ($amount, $currency = null) use ($uiCurrency, $currencyRateToMain) {
        $amount = (float) $amount;
        $sourceCurrency = strtoupper((string) ($currency ?: 'USD'));
        $targetCurrency = $uiCurrency ?: $sourceCurrency;

        $sourceRate = (float) ($currencyRateToMain[$sourceCurrency] ?? 1.0);
        $targetRate = (float) ($currencyRateToMain[$targetCurrency] ?? 1.0);

        if ($targetCurrency !== $sourceCurrency) {
            $mainAmount = $sourceRate > 0 ? ($amount / $sourceRate) : $amount;
            $amount = $mainAmount * ($targetRate > 0 ? $targetRate : 1.0);
        }

        $symbol = match ($targetCurrency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            'RUB' => '₽',
            default => '$',
        };

        $decimals = in_array($targetCurrency, ['TRY', 'RUB'], true) ? 0 : 2;

        return $symbol . number_format($amount, $decimals, ',', '.');
    };

    $offerUiPrice = function ($offer) use ($uiMoney) {
        return $uiMoney(
            $offer['total_amount'] ?? $offer['amount'] ?? 0,
            $offer['currency'] ?? $offer['price_currency'] ?? 'USD'
        );
    };

    $fareUiPrice = function ($fareOption, $offer) use ($uiMoney) {
        return $uiMoney(
            $fareOption['total_amount'] ?? $fareOption['price'] ?? $offer['total_amount'] ?? 0,
            $fareOption['currency'] ?? $offer['currency'] ?? $offer['price_currency'] ?? 'USD'
        );
    };

    $airlineDisplayName = function ($offer) use ($isSandboxSearch, $ft) {
        $name = trim((string) ($offer['airline_name'] ?? 'Airline'));

        if ($isSandboxSearch && strtolower($name) === 'duffel airways') {
            return 'Duffel Airways' . ' (' . $ft('test_label') . ')';
        }

        return $name;
    };

    $priceModeLabel = $isSandboxSearch ? $ft('test_price') : $ft('live_price');

    $adultOptionLabel = function ($count) use ($ftCount) {
        return $ftCount($count, 'adult_one', 'adult_many');
    };

    $passengerSummary = function ($count) use ($ftCount) {
        return $ftCount($count, 'passenger_one', 'passenger_many');
    };

    $offerSummary = function ($count) use ($ftCount) {
        return $ftCount($count, 'offer_one', 'offer_many');
    };

    $humanBadge = function ($badge) use ($ft) {
        $badge = trim((string) $badge);
        $key = strtolower($badge);

        return match ($key) {
            'en ucuz', 'cheapest', 'самые дешевые', 'الأرخص' => $ft('cheapest'),
            'en hızlı', 'en hizli', 'fastest', 'самые быстрые', 'الأسرع' => $ft('fastest'),
            'önerilen', 'onerilen', 'recommended', 'рекомендуемые', 'موصى به' => $ft('recommended'),
            'direkt', 'direct', 'прямой', 'مباشر' => $ft('direct'),
            default => $badge,
        };
    };

    $humanStopLabel = function ($label) use ($ft, $ftCount) {
        $label = trim((string) $label);
        $lower = mb_strtolower($label);

        if (str_contains($lower, 'direkt') || str_contains($lower, 'direct') || str_contains($lower, 'прям') || str_contains($lower, 'مباشر')) {
            return $ft('direct_flight');
        }

        if (preg_match('/(\d+)/', $label, $match)) {
            $count = (int) $match[1];

            return $ftCount($count, 'stop_one', 'stop_many');
        }

        return $label;
    };
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
                <a href="{{ url('/') }}">{{ $ft('breadcrumb_home') }}</a>
                <span>/</span>
                <span>{{ $ft('breadcrumb_results') }}</span>
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
                    <a href="{{ url('/') }}">{{ $ft('nav_home') }}</a>
                    <a href="{{ $routeName }}">{{ $ft('nav_flights') }}</a>
                    <a href="#support">{{ $ft('nav_support') }}</a>
                    <a href="{{ url('/login') }}" class="tsa-login-chip">{{ $ft('nav_login') }}</a>
                </div>
            </div>

            @if($isSandboxSearch)
                <div class="tsa-sandbox-warning">
                    <strong>{{ $ft('sandbox_badge') }}</strong>
                    <span>{{ $ft('sandbox_message') }}</span>
                </div>
            @endif

            <form class="tsa-flight-panel tsa-search-strip" method="GET" action="{{ $routeName }}">
                  <div class="tsa-search-field tsa-search-field--airport" data-airport-picker>
                      <label>{{ $ft('from') }}</label>
                      <input type="text"
                             class="tsa-airport-input"
                             value="{{ $originDisplay }}"
                             placeholder="{{ $ft('airport_placeholder') }}"
                             autocomplete="off"
                             spellcheck="false"
                             autocorrect="off"
                             autocapitalize="characters"
                             data-airport-url="{{ $airportSearchUrl }}"
                             data-airport-role="origin">
                      <input type="hidden" name="origin" class="tsa-airport-value" value="{{ $criteria['origin'] }}">
                      <span class="tsa-airport-caret">⌄</span>
                      <div class="tsa-airport-dropdown"></div>
                      <small>{{ $ft('example_from') }}</small>
                  </div>
                  <div class="tsa-search-field tsa-search-field--airport" data-airport-picker>
                      <label>{{ $ft('to') }}</label>
                      <input type="text"
                             class="tsa-airport-input"
                             value="{{ $destinationDisplay }}"
                             placeholder="{{ $ft('airport_placeholder') }}"
                             autocomplete="off"
                             spellcheck="false"
                             autocorrect="off"
                             autocapitalize="characters"
                             data-airport-url="{{ $airportSearchUrl }}"
                             data-airport-role="destination">
                      <input type="hidden" name="destination" class="tsa-airport-value" value="{{ $criteria['destination'] }}">
                      <span class="tsa-airport-caret">⌄</span>
                      <div class="tsa-airport-dropdown"></div>
                      <small>{{ $ft('example_to') }}</small>
                  </div>
                <div class="tsa-search-field">
                    <label>{{ $ft('departure') }}</label>
                    <input type="date" name="departure_date" value="{{ $criteria['departure_date'] }}">
                    <small>{{ $ft('departure_date') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ $ft('return') }}</label>
                    <input type="date" name="return_date" value="{{ $criteria['return_date'] }}">
                    <small>{{ $ft('optional_return_date') }}</small>
                </div>
                <div class="tsa-search-field">
                    <label>{{ $ft('passenger') }}</label>
                    <select name="adult_count">
                        @for ($adult = 1; $adult <= 6; $adult++)
                            <option value="{{ $adult }}" @selected($criteria['adult_count'] == $adult)>{{ $adultOptionLabel($adult) }}</option>
                        @endfor
                    </select>
                    <small>{{ $ft('passenger_count') }}</small>
                </div>
                <button class="tsa-search-submit" type="submit">{{ $ft('search_flights') }}</button>
            </form>

            @if ($offers->isEmpty())
                <div class="tsa-empty-card">
                    <h2>{{ $ft('empty_title') }}</h2>
                    <p>{{ $ft('empty_body') }}</p>
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
                                    <h3>{{ $ft('price_alert_title') }}</h3>
                                    <p>{{ $ft('price_alert_body') }}</p>
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
                                <h4>{{ $ft('recommendations') }}</h4>
                                <label class="tsa-filter-option">
                                    <input type="checkbox" name="direct_only" value="1" @checked($criteria['direct_only']) onchange="this.form.submit()">
                                    <span>{{ $ft('direct') }}</span>
                                </label>
                            </div>

                            <div class="tsa-filter-group">
                                <h4>{{ $ft('airlines') }}</h4>
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
                                    <span>{{ $ft('edit_search') }}</span>
                                    <span>{{ $ft('compare_dates') }}</span>
                                </div>
                            </div>
                            <div class="tsa-route-meta">
                                <span>{{ \Carbon\Carbon::parse($criteria['departure_date'])->translatedFormat('d M D') }}</span>
                                <span>{{ $passengerSummary($criteria['passenger_total']) }}</span>
                                <span>{{ $offerSummary($offers->count()) }}</span>
                            </div>
                        </div>

                        <div class="tsa-day-tabs">
                            <div class="tsa-day-tab">{{ \Carbon\Carbon::parse($criteria['departure_date'])->copy()->subDay()->translatedFormat('d M D') }}</div>
                            <div class="tsa-day-tab is-active">{{ \Carbon\Carbon::parse($criteria['departure_date'])->translatedFormat('d M D') }}</div>
                            <div class="tsa-day-tab">{{ \Carbon\Carbon::parse($criteria['departure_date'])->copy()->addDay()->translatedFormat('d M D') }}</div>
                        </div>

                        <div class="tsa-sort-tabs">
                            @foreach (['recommended' => $ft('recommended'), 'price' => $ft('cheapest'), 'duration' => $ft('fastest'), 'departure' => $ft('direct_first')] as $sortKey => $sortLabel)
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
                                            <span class="tsa-badge">{{ $humanBadge($badge) }}</span>
                                        @endforeach
                                    </div>

                                    <div class="tsa-airline-row">
                                        <div class="tsa-airline-identity">
                                            <div class="tsa-airline-logo">{{ $offer['airline_initials'] ?? 'FL' }}</div>
                                            <div>
                                                <h2 class="tsa-airline-name">{{ $airlineDisplayName($offer) }}</h2>
                                                <div class="tsa-airline-meta">{{ $humanFare($offer['selected_fare']['label'] ?? '') }} • {{ $priceModeLabel }}</div>
                                            </div>
                                        </div>
                                        <div class="tsa-price-rail">{{ $offerUiPrice($offer) }}</div>
                                    </div>

                                    <div class="tsa-timeline">
                                        <div class="tsa-time-block">
                                            <strong>{{ $offer['departure_time_label'] }}</strong>
                                            <span>{{ $offer['origin'] }}</span>
                                          <small>{{ $ft('departure_short') }}</small>
                                        </div>
                                        <div class="tsa-timeline-center">
                                            <strong>{{ $offer['duration_label'] }}</strong>
                                            <div class="tsa-timeline-line"></div>
                                            <span>{{ $humanStopLabel($offer['stop_label'] ?? '') }}</span>
                                        </div>
                                        <div class="tsa-time-block" style="text-align:right">
                                            <strong>{{ $offer['arrival_time_label'] }}</strong>
                                            <span>{{ $offer['destination'] }}</span>
                                          <small>{{ $ft('arrival_short') }}</small>
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
                                                <span class="tsa-pill">{{ $humanFeature($feature) }}</span>
                                            @endforeach
                                        </div>
                                        <div class="tsa-card-actions">
                                            <button type="button" class="tsa-secondary-btn js-open-modal" data-modal-id="modal-{{ $offer['id'] }}">{{ $ft('package_details') }}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tsa-result-aside">
                                    <div class="price">{{ $offerUiPrice($offer) }}</div>
                                    <div class="currency">{{ $humanFare($offer['selected_fare']['label'] ?? '') }}</div>
                                    @if($isTurkeyDomestic)
                                        <div class="tsa-ui-currency-note">
                                            {{ $isSandboxSearch ? $ft('test_not_for_sale') : $ft('price') }}
                                        </div>
                                    @endif
                                    <button type="button" class="tsa-primary-btn js-open-modal" data-modal-id="modal-{{ $offer['id'] }}">{{ $ft('select_continue') }}</button>
                                </div>
                            </article>

                            <div class="tsa-modal" id="modal-{{ $offer['id'] }}">
                                <div class="tsa-modal-shell">
                                    <div class="tsa-modal-head">
                                        <h3>{{ $ft('choose_package') }}</h3>
                                        <button type="button" class="tsa-modal-close js-close-modal">×</button>
                                    </div>
                                    <div class="tsa-modal-grid">
                                        @foreach ($offer['fare_options'] as $fareOption)
                                            <div class="tsa-modal-card {{ $fareOption['is_selected'] ? 'is-selected' : '' }}">
                                                <h4>
                                                    <span>{{ $humanFare($fareOption['label'] ?? '') }}</span>
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
                                                    <strong>{{ $ft('package_features') }}</strong>
                                                    <ul>
                                                        @forelse ($fareOption['features'] as $feature)
                                                            <li>{{ $humanFeature($feature) }}</li>
                                                        @empty
                                                            <li>{{ __('Standart paket') }}</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                                @php
                                                    $fareTotalLabel = $fareOption['total_price_label'] ?? $offer['display_price'];
                                                    $fareDeltaLabel = $fareOption['delta_label'] ?? null;
                                                    $zeroDeltaLabels = ['$0', '$0.00', '€0', '€0.00', '₺0', '₺0.00', '0'];
                                                @endphp
                                                <div class="tsa-modal-price">{{ $fareUiPrice($fareOption, $offer) }}</div>
                                                @if(!empty($fareDeltaLabel) && !in_array($fareDeltaLabel, $zeroDeltaLabels, true))
                                                    <div class="tsa-modal-delta">{{ $ft('price_difference') }}: {{ $fareDeltaLabel }}</div>
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
                                                    <button type="submit" class="tsa-modal-submit" style="width:100%">{{ __('Bu paketle devam et') }}</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="tsa-modal-footer">
                                        <div class="tsa-modal-selection">
                                            <strong>{{ $airlineDisplayName($offer) }}</strong>
                                            <span>{{ $humanFare($offer['selected_fare']['label'] ?? '') }} • {{ $offerUiPrice($offer) }}</span>
                                        </div>
                                        <button type="button" class="tsa-secondary-btn js-close-modal">{{ $ft('close') }}</button>
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

                @if ($selectedOfferData && !empty($criteria['selected_offer']))
                    <div class="tsa-summary-card" style="margin-top:18px">
                        <span class="tsa-badge">{{ $ft('selected_flight') }}</span>
                        <h3>{{ $ft('selected_summary') }}</h3>
                        <div class="tsa-summary-body">
                            <strong>{{ $airlineDisplayName($selectedOfferData) }}</strong>
                            <div>{{ $selectedOfferData['route_label'] }}</div>
                        </div>
                        <div class="tsa-summary-grid">
                            <div class="tsa-summary-box">
                                <label>{{ $ft('price') }}</label>
                                <strong>{{ $offerUiPrice($selectedOfferData) }}</strong>
                            </div>
                            <div class="tsa-summary-box">
                                <label>{{ __('Süre') }}</label>
                                <strong>{{ $selectedOfferData['duration_label'] }}</strong>
                            </div>
                            <div class="tsa-summary-box">
                                <label>{{ $ft('package') }}</label>
                                <strong>{{ $humanFare($selectedOfferData['selected_fare']['label'] ?? '') }}</strong>
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
                            <a href="#offer-{{ $selectedOfferData['id'] }}" class="tsa-primary-btn">{{ $ft('back_selected') }}</a>
                            <a href="#support" class="tsa-secondary-btn">{{ $ft('nav_support') }}</a>
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


@push('css')
<style id="tsa-supplier-airport-autocomplete-css">
    .tsa-search-field--airport{position:relative}
    .tsa-airport-input{padding-right:34px}
    .tsa-airport-input.is-invalid{color:#b42318!important}
    .tsa-airport-caret{position:absolute;right:16px;top:52px;color:#6d7d76;font-size:18px;pointer-events:none}
    .tsa-airport-dropdown{display:none;position:absolute;left:14px;right:14px;top:calc(100% - 6px);background:#fff;border:1px solid #dbe7e1;border-radius:18px;box-shadow:0 22px 45px rgba(17,31,27,.18);z-index:9999;overflow:hidden;max-height:320px;overflow-y:auto}
    .tsa-airport-dropdown.is-open{display:block}
    .tsa-airport-item{display:flex;gap:12px;padding:13px 15px;cursor:pointer;border-bottom:1px solid #edf3f0;align-items:flex-start}
    .tsa-airport-item:last-child{border-bottom:0}
    .tsa-airport-item:hover{background:#f4faf7}
    .tsa-airport-code{min-width:48px;padding:5px 8px;border-radius:10px;background:#eaf6f2;color:#0f766e;font-size:13px;font-weight:900;text-align:center}
    .tsa-airport-title{display:block;font-size:15px;font-weight:800;color:#172b24;line-height:1.25}
    .tsa-airport-desc{display:block;font-size:13px;color:#667870;margin-top:3px;line-height:1.25}
    .tsa-airport-empty{padding:14px 15px;font-size:14px;color:#667870}
</style>
@endpush


@push('js')
<script>
(function () {
    if (window.__tsaSupplierAirportSearchLoaded) return;
    window.__tsaSupplierAirportSearchLoaded = true;

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var self = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(self, args);
            }, delay || 220);
        };
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
        });
    }

    function getItems(payload) {
        var items = payload && (payload.data || payload.results || []);
        if (!Array.isArray(items)) items = Object.values(items || {});
        return items;
    }

    function renderAirportDropdown(input, items) {
        var picker = input.closest('[data-airport-picker]');
        var dropdown = picker.querySelector('.tsa-airport-dropdown');
        dropdown.innerHTML = '';

        if (!items.length) {
            dropdown.innerHTML = '<div class="tsa-airport-empty">Airport not found. Try IATA code or city name.</div>';
            dropdown.classList.add('is-open');
            return;
        }

        items.forEach(function (item) {
            var code = String(item.code || item.id || '').toUpperCase();
            var title = item.name || item.title || item.text || code;
            var desc = item.address || item.desc || item.country || '';
            var cleanTitle = title.replace(code + ' - ', '');
            var display = code ? code + ' · ' + cleanTitle : cleanTitle;

            var row = document.createElement('div');
            row.className = 'tsa-airport-item';
            row.innerHTML =
                '<span class="tsa-airport-code">' + escapeHtml(code) + '</span>' +
                '<span>' +
                    '<span class="tsa-airport-title">' + escapeHtml(cleanTitle) + '</span>' +
                    (desc ? '<span class="tsa-airport-desc">' + escapeHtml(desc) + '</span>' : '') +
                '</span>';

            row.addEventListener('mousedown', function (event) {
                event.preventDefault();
                input.value = display;
                input.classList.remove('is-invalid');
                picker.querySelector('.tsa-airport-value').value = code;
                dropdown.classList.remove('is-open');
            });

            dropdown.appendChild(row);
        });

        dropdown.classList.add('is-open');
    }

    function fetchAirports(input, query, clearValue) {
        var picker = input.closest('[data-airport-picker]');
        var dropdown = picker.querySelector('.tsa-airport-dropdown');
        var hidden = picker.querySelector('.tsa-airport-value');
        var url = input.getAttribute('data-airport-url');

        if (clearValue) {
            hidden.value = '';
            input.classList.remove('is-invalid');
        }

        dropdown.innerHTML = '<div class="tsa-airport-empty">Searching airports...</div>';
        dropdown.classList.add('is-open');

        fetch(url + '?search=' + encodeURIComponent(query || '') + '&_=' + Date.now(), {
            headers: {'Accept': 'application/json'}
        })
        .then(function (response) { return response.json(); })
        .then(function (payload) { renderAirportDropdown(input, getItems(payload)); })
        .catch(function () {
            dropdown.innerHTML = '<div class="tsa-airport-empty">Airport search could not be loaded.</div>';
            dropdown.classList.add('is-open');
        });
    }

    var searchAirports = debounce(function (input) {
        fetchAirports(input, input.value.trim(), true);
    }, 220);

    document.addEventListener('input', function (event) {
        if (!event.target.classList.contains('tsa-airport-input')) return;
        searchAirports(event.target);
    });

    document.addEventListener('focusin', function (event) {
        if (!event.target.classList.contains('tsa-airport-input')) return;
        var picker = event.target.closest('[data-airport-picker]');
        var hidden = picker.querySelector('.tsa-airport-value');
        event.target.select();
        fetchAirports(event.target, hidden.value || event.target.value.trim(), false);
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.tsa-airport-dropdown.is-open').forEach(function (dropdown) {
            if (!dropdown.closest('[data-airport-picker]').contains(event.target)) {
                dropdown.classList.remove('is-open');
            }
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.classList.contains('tsa-search-strip')) return;

        var origin = form.querySelector('input[name="origin"]');
        var destination = form.querySelector('input[name="destination"]');
        var invalid = false;

        [origin, destination].forEach(function (hidden) {
            var picker = hidden.closest('[data-airport-picker]');
            var input = picker.querySelector('.tsa-airport-input');

            if (!hidden.value || hidden.value.length !== 3) {
                input.classList.add('is-invalid');
                invalid = true;
            }
        });

        if (invalid) {
            event.preventDefault();
            alert('Please select origin and destination from the airport list.');
            return;
        }

        if (origin.value === destination.value) {
            event.preventDefault();
            alert('Origin and destination cannot be the same airport.');
        }
    });
})();
</script>
@endpush


@push('css')
<style id="tsa-results-ui-cleanup-css">
    .tsa-search-strip{grid-template-columns:1.15fr 1.15fr .9fr .9fr .72fr .72fr}
    .tsa-search-field{min-width:0}
    .tsa-search-field input,
    .tsa-search-field select{
        font-size:21px!important;
        line-height:1.2!important;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .tsa-search-field small{
        min-height:18px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .tsa-airport-dropdown{
        min-width:420px;
        width:max-content;
        max-width:560px;
    }
    .tsa-airport-title{
        max-width:420px;
        word-break:normal;
    }
    .tsa-result-card{
        grid-template-columns:minmax(0,1fr) 210px;
    }
    .tsa-airline-meta{
        font-size:15px;
        color:#64756e;
    }
    .tsa-card-actions .tsa-secondary-btn{
        min-width:150px;
    }
    .tsa-result-aside .price{
        font-size:40px;
        line-height:1;
    }
    .tsa-result-aside .tsa-primary-btn{
        font-size:20px;
        white-space:nowrap;
    }
    .tsa-summary-card{
        position:static;
    }
    @media (max-width:1280px){
        .tsa-airport-dropdown{
            min-width:320px;
            max-width:calc(100vw - 48px);
        }
    }
</style>
@endpush


@push('css')
<style id="tsa-ui-currency-note-css">
    .tsa-ui-currency-note{
        margin-top:-10px;
        font-size:12px;
        line-height:1.35;
        color:#6b7a73;
        text-align:center;
    }
</style>
@endpush


@push('css')
<style id="tsa-sandbox-warning-css">
    .tsa-sandbox-warning{
        display:flex;
        gap:14px;
        align-items:center;
        padding:16px 20px;
        margin-bottom:18px;
        border:1px solid #f4c48a;
        border-radius:22px;
        background:#fff8ec;
        color:#6d4608;
        box-shadow:0 14px 32px rgba(120,80,20,.08);
    }
    .tsa-sandbox-warning strong{
        padding:6px 10px;
        border-radius:999px;
        background:#f59e0b;
        color:#fff;
        white-space:nowrap;
        font-size:13px;
    }
    .tsa-sandbox-warning span{
        font-size:15px;
        line-height:1.45;
        font-weight:600;
    }
    .tsa-result-card{
        grid-template-columns:minmax(0,1fr) 260px!important;
    }
    .tsa-result-aside{
        padding:24px 20px!important;
    }
    .tsa-result-aside .tsa-primary-btn{
        width:100%;
        max-width:100%;
        font-size:16px!important;
        line-height:1.2;
        padding:16px 12px!important;
        text-align:center;
        white-space:normal!important;
    }
    .tsa-result-aside .price{
        font-size:38px!important;
    }
    .tsa-card-actions{
        justify-content:flex-end;
    }
    .tsa-card-actions .tsa-secondary-btn{
        padding:13px 16px;
        white-space:nowrap;
    }
</style>
@endpush


@push('css')
<style id="tsa-results-polish-v1-css">
    .tsa-flight-shell{
        background:
            radial-gradient(circle at top left, rgba(15,118,110,.08), transparent 34%),
            linear-gradient(135deg,#fbf7ef 0%,#f6f5ef 45%,#eef7f3 100%)!important;
    }

    .tsa-flight-container{
        max-width:1360px!important;
    }

    .tsa-topbar{
        padding:18px 22px!important;
        border-radius:22px!important;
    }

    .tsa-brand-mark{
        width:42px!important;
        height:42px!important;
        border-radius:14px!important;
        font-size:21px!important;
    }

    .tsa-topnav{
        gap:14px!important;
    }

    .tsa-topnav a{
        padding:10px 12px;
        border-radius:12px;
    }

    .tsa-topnav a:hover{
        background:#f2f7f4;
    }

    .tsa-login-chip{
        padding:10px 14px!important;
        border-radius:14px!important;
    }

    .tsa-sandbox-warning{
        margin-bottom:14px!important;
        border-radius:18px!important;
        padding:13px 16px!important;
    }

    .tsa-search-strip{
        grid-template-columns:1.25fr 1.25fr .82fr .82fr .62fr 148px!important;
        gap:12px!important;
        padding:16px!important;
        border-radius:24px!important;
    }

    .tsa-search-field{
        padding:14px 14px 12px!important;
        border-radius:17px!important;
    }

    .tsa-search-field label{
        margin-bottom:6px!important;
        font-size:11px!important;
    }

    .tsa-search-field input,
    .tsa-search-field select{
        font-size:18px!important;
        min-height:28px;
    }

    .tsa-search-field small{
        font-size:12px!important;
        margin-top:6px!important;
    }

    .tsa-search-submit{
        min-height:76px;
        border-radius:17px!important;
        font-size:16px!important;
        box-shadow:0 14px 28px rgba(15,118,110,.18);
    }

    .tsa-layout{
        grid-template-columns:240px minmax(0,1fr) 230px!important;
        gap:14px!important;
    }

    .tsa-sidebar,
    .tsa-support-card,
    .tsa-search-summary,
    .tsa-day-tabs,
    .tsa-sort-tabs,
    .tsa-result-card{
        border-radius:20px!important;
    }

    .tsa-sidebar{
        padding:16px!important;
    }

    .tsa-rail-icons{
        display:none!important;
    }

    .tsa-filter-card{
        border-radius:18px!important;
        padding:15px!important;
    }

    .tsa-filter-card h3{
        font-size:13px!important;
    }

    .tsa-filter-card p{
        font-size:13px!important;
    }

    .tsa-filter-group h4{
        font-size:13px!important;
    }

    .tsa-filter-option{
        font-size:14px!important;
    }

    .tsa-search-summary{
        padding:17px 20px!important;
    }

    .tsa-route{
        font-size:19px!important;
    }

    .tsa-route-meta{
        margin-top:8px;
        flex-wrap:wrap;
    }

    .tsa-edit-links{
        gap:10px!important;
        font-size:13px!important;
    }

    .tsa-edit-links span{
        padding:7px 10px;
        border-radius:999px;
        background:#f3f7f5;
        color:#31544a;
    }

    .tsa-day-tab,
    .tsa-sort-tab{
        padding:13px 14px!important;
        font-size:14px!important;
    }

    .tsa-result-card{
        grid-template-columns:minmax(0,1fr) 240px!important;
        border:1px solid #dce9e3!important;
        box-shadow:0 18px 38px rgba(15,50,34,.065)!important;
        transition:transform .18s ease, box-shadow .18s ease;
    }

    .tsa-result-card:hover{
        transform:translateY(-2px);
        box-shadow:0 24px 48px rgba(15,50,34,.10)!important;
    }

    .tsa-result-main{
        padding:20px 22px!important;
    }

    .tsa-card-badges{
        margin-bottom:10px!important;
    }

    .tsa-badge{
        font-size:12px!important;
        padding:5px 10px!important;
    }

    .tsa-airline-row{
        align-items:center!important;
        margin-bottom:16px!important;
    }

    .tsa-airline-identity{
        display:flex;
        align-items:center;
        gap:12px;
        min-width:0;
    }

    .tsa-airline-logo{
        width:42px;
        height:42px;
        flex:0 0 42px;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#eef7f3;
        border:1px solid #dce9e3;
        color:#0f766e;
        font-size:13px;
        font-weight:900;
        letter-spacing:.03em;
    }

    .tsa-airline-name{
        font-size:18px!important;
        line-height:1.25!important;
        max-width:430px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    .tsa-airline-meta{
        margin-top:3px!important;
        font-size:13px!important;
    }

    .tsa-price-rail{
        display:none!important;
    }

    .tsa-timeline{
        grid-template-columns:145px minmax(180px,1fr) 145px!important;
        padding:16px 0!important;
    }

    .tsa-time-block strong{
        font-size:24px!important;
        letter-spacing:-.02em;
    }

    .tsa-time-block span{
        display:block;
        margin-top:2px;
        font-size:15px!important;
        font-weight:800;
        color:#1e3830!important;
    }

    .tsa-time-block small{
        display:block;
        margin-top:3px;
        font-size:12px;
        color:#78877f;
    }

    .tsa-timeline-center strong{
        font-size:15px!important;
        color:#40564e!important;
    }

    .tsa-timeline-center span{
        display:inline-flex;
        margin-top:2px;
        padding:5px 10px;
        border-radius:999px;
        background:#f2f7f4;
        color:#0f766e;
        font-size:12px!important;
        font-weight:800;
    }

    .tsa-timeline-line{
        position:relative;
        height:2px!important;
        background:#c9d8d1!important;
        margin:9px 34px!important;
    }

    .tsa-timeline-line:before,
    .tsa-timeline-line:after{
        content:"";
        position:absolute;
        top:50%;
        width:8px;
        height:8px;
        border-radius:50%;
        background:#0f766e;
        transform:translateY(-50%);
    }

    .tsa-timeline-line:before{
        left:-2px;
    }

    .tsa-timeline-line:after{
        right:-2px;
    }

    .tsa-card-footer{
        padding-top:14px!important;
        align-items:flex-end!important;
    }

    .tsa-pill-row{
        gap:7px!important;
    }

    .tsa-pill{
        padding:7px 10px!important;
        font-size:12px!important;
        border:1px solid #e0ebe5;
        background:#fafcfb!important;
    }

    .tsa-card-actions .tsa-secondary-btn{
        border-radius:14px!important;
        padding:11px 13px!important;
        font-size:13px!important;
    }

    .tsa-result-aside{
        background:linear-gradient(180deg,#fbfdfc 0%,#f4faf7 100%);
        border-left:1px solid #e1ece7!important;
        padding:20px 18px!important;
        gap:12px!important;
        align-items:stretch;
    }

    .tsa-result-aside .price{
        font-size:34px!important;
        letter-spacing:-.04em;
        color:#10251f!important;
    }

    .tsa-result-aside .currency{
        font-size:13px!important;
        font-weight:800;
        color:#50645c!important;
    }

    .tsa-ui-currency-note{
        margin-top:-4px!important;
        padding:8px 10px;
        border-radius:12px;
        background:#fff8ec;
        color:#76520d!important;
        font-size:11px!important;
    }

    .tsa-result-aside .tsa-primary-btn{
        margin-top:2px;
        border-radius:15px!important;
        font-size:15px!important;
        padding:14px 10px!important;
        box-shadow:0 14px 28px rgba(15,118,110,.18);
    }

    .tsa-support-card{
        padding:18px!important;
    }

    .tsa-support-card .icon{
        width:52px!important;
        height:52px!important;
        border-radius:18px!important;
        font-size:24px!important;
    }

    .tsa-support-card h3{
        font-size:16px!important;
    }

    .tsa-support-card p{
        font-size:13px!important;
    }

    .tsa-modal-shell{
        border-radius:24px!important;
        max-height:88vh;
        overflow:auto;
    }

    .tsa-modal-head h3{
        font-size:24px!important;
    }

    .tsa-modal-card{
        border-radius:20px!important;
    }

    .tsa-modal-submit{
        border-radius:15px!important;
        padding:14px 16px!important;
    }

    @media (max-width:1280px){
        .tsa-layout{
            grid-template-columns:220px minmax(0,1fr)!important;
        }

        .tsa-support-card{
            display:none!important;
        }

        .tsa-search-strip{
            grid-template-columns:1fr 1fr 1fr!important;
        }

        .tsa-search-submit{
            min-height:62px;
        }
    }

    @media (max-width:991px){
        .tsa-topnav{
            flex-wrap:wrap;
        }

        .tsa-search-strip{
            grid-template-columns:1fr!important;
        }

        .tsa-layout{
            grid-template-columns:1fr!important;
        }

        .tsa-sidebar{
            order:2;
        }

        .tsa-result-card{
            grid-template-columns:1fr!important;
        }

        .tsa-result-aside{
            border-left:0!important;
            border-top:1px solid #e1ece7!important;
        }

        .tsa-timeline{
            grid-template-columns:1fr!important;
            gap:14px;
        }

        .tsa-timeline-center{
            order:2;
        }

        .tsa-time-block[style]{
            text-align:left!important;
        }

        .tsa-airline-row,
        .tsa-card-footer{
            align-items:flex-start!important;
            flex-direction:column;
        }

        .tsa-card-actions{
            width:100%;
            justify-content:stretch!important;
        }

        .tsa-card-actions .tsa-secondary-btn{
            width:100%;
        }
    }
</style>
@endpush


@push('css')
<style id="tsa-results-final-touches-css">
    .tsa-sort-tabs{
        display:grid!important;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px!important;
        padding:8px!important;
        border:1px solid #dce9e3!important;
        background:#fff!important;
        box-shadow:0 14px 30px rgba(15,50,34,.05)!important;
    }

    .tsa-sort-tabs form{
        margin:0!important;
        min-width:0;
    }

    .tsa-sort-tab{
        width:100%;
        border:1px solid #dce9e3!important;
        border-radius:15px!important;
        background:#f8fbfa!important;
        color:#12251f!important;
        box-shadow:none!important;
        font-size:13px!important;
        font-weight:800!important;
        white-space:nowrap;
    }

    .tsa-sort-tab.is-active,
    .tsa-sort-tab.active{
        border-color:#0f766e!important;
        background:#0f766e!important;
        color:#fff!important;
        box-shadow:0 12px 24px rgba(15,118,110,.18)!important;
    }

    .tsa-sort-tab:hover{
        border-color:#0f766e!important;
        color:#0f766e!important;
    }

    .tsa-sort-tab.is-active:hover,
    .tsa-sort-tab.active:hover{
        color:#fff!important;
    }

    .tsa-day-tabs{
        overflow:hidden;
        border:1px solid #dce9e3!important;
        background:#fff!important;
        box-shadow:0 14px 30px rgba(15,50,34,.05)!important;
    }

    .tsa-day-tab{
        border:0!important;
        border-right:1px solid #e3eee8!important;
        background:#fff!important;
        color:#153229!important;
        font-weight:900!important;
    }

    .tsa-day-tab:last-child{
        border-right:0!important;
    }

    .tsa-day-tab.is-active,
    .tsa-day-tab.active{
        background:#0f766e!important;
        color:#fff!important;
    }

    .tsa-search-summary{
        box-shadow:0 14px 30px rgba(15,50,34,.05)!important;
    }

    .tsa-result-list{
        margin-bottom:80px;
    }

    .bravo_footer,
    .bravo-newsletter,
    .footer,
    footer{
        display:none!important;
    }

    @media (max-width:991px){
        .tsa-sort-tabs{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }
</style>
@endpush


@push('css')
<style id="tsa-results-turkey-ui-v1-css">
    /* Booking Core ana menüsü kalacak; uçuş içindeki ikinci Diana menüsü gizlenir */
    .tsa-topbar{
        display:none!important;
    }

    .tsa-flight-shell{
        padding-top:34px!important;
    }

    /* Tema breadcrumb alanını uçuş sayfasında sadeleştir */
    .bravo-breadcrumb,
    .breadcrumb-page,
    .page-template-content .bravo-breadcrumb{
        display:none!important;
    }

    /* Sonuç sayfasında Booking Core newsletter/footer alanlarını kesin gizle */
    .bravo_footer,
    .bravo_footer *,
    .bravo-newsletter,
    .bravo-newsletter *,
    .bravo-subscribe-form,
    .bravo-subscribe-form *,
    .newsletter,
    .newsletter *,
    .footer,
    .footer *,
    footer,
    footer *{
        display:none!important;
        visibility:hidden!important;
        height:0!important;
        min-height:0!important;
        max-height:0!important;
        overflow:hidden!important;
        padding:0!important;
        margin:0!important;
        border:0!important;
    }

    body{
        background:#f7faf8!important;
    }

    .tsa-flight-shell{
        background:
            radial-gradient(circle at top left, rgba(0,85,130,.055), transparent 32%),
            linear-gradient(135deg,#fbf8f1 0%,#f7f8f4 42%,#eef7f3 100%)!important;
    }

    .tsa-sandbox-warning{
        border-color:#f1c67c!important;
        background:#fff8ed!important;
        color:#704600!important;
    }

    .tsa-sandbox-warning strong{
        background:#f59e0b!important;
    }

    .tsa-search-submit,
    .tsa-sort-tab.is-active,
    .tsa-sort-tab.active,
    .tsa-day-tab.is-active,
    .tsa-day-tab.active,
    .tsa-result-aside .tsa-primary-btn,
    .tsa-modal-submit{
        background:#137f73!important;
    }

    .tsa-search-submit:hover,
    .tsa-result-aside .tsa-primary-btn:hover,
    .tsa-modal-submit:hover{
        background:#0f6f65!important;
    }

    .tsa-airline-logo,
    .tsa-timeline-line:before,
    .tsa-timeline-line:after{
        color:#137f73!important;
        background:#eaf7f4!important;
        border-color:#cfe8e1!important;
    }

    .tsa-time-block strong,
    .tsa-result-aside .price,
    .tsa-airline-name,
    .tsa-route{
        color:#0f2430!important;
    }

    .tsa-ui-currency-note{
        background:#eef7f3!important;
        color:#137f73!important;
        font-weight:800;
    }

    .tsa-result-list{
        margin-bottom:110px!important;
    }

    .tsa-result-card:last-child{
        margin-bottom:40px!important;
    }
</style>
@endpush




@push('css')
@if(app()->getLocale() === 'ar')
<style id="tsa-flight-rtl-css">
    .tsa-flight-shell{
        direction:rtl;
    }
    .tsa-flight-shell .tsa-search-field,
    .tsa-flight-shell .tsa-route,
    .tsa-flight-shell .tsa-route-meta,
    .tsa-flight-shell .tsa-airline-row,
    .tsa-flight-shell .tsa-card-footer,
    .tsa-flight-shell .tsa-support-card,
    .tsa-flight-shell .tsa-filter-card{
        text-align:right;
    }
    .tsa-flight-shell .tsa-result-card{
        direction:rtl;
    }
    .tsa-flight-shell .tsa-result-aside{
        border-left:0!important;
        border-right:1px solid #e1ece7!important;
    }
    .tsa-flight-shell .tsa-time-block[style]{
        text-align:left!important;
    }
    .tsa-flight-shell .tsa-card-actions{
        justify-content:flex-start;
    }
</style>
@endif
@endpush




@push('css')
<style id="tsa-flight-currency-binding-source-ok">
    .tsa-flight-shell{--tsa-flight-currency-binding:booking-core-current-currency;}
</style>
@endpush


@push('css')
<style id="tsa-flight-footer-css-only-hide-v1">
    body:has(.tsa-flight-shell) .bravo_footer,
    body:has(.tsa-flight-shell) .bravo_footer *,
    body:has(.tsa-flight-shell) .bravo-newsletter,
    body:has(.tsa-flight-shell) .bravo-newsletter *,
    body:has(.tsa-flight-shell) .bravo-subscribe-form,
    body:has(.tsa-flight-shell) .bravo-subscribe-form *,
    body:has(.tsa-flight-shell) .newsletter,
    body:has(.tsa-flight-shell) .newsletter *,
    body:has(.tsa-flight-shell) .footer,
    body:has(.tsa-flight-shell) .footer *,
    body:has(.tsa-flight-shell) footer,
    body:has(.tsa-flight-shell) footer *,
    body:has(.tsa-flight-shell) [class*="footer"],
    body:has(.tsa-flight-shell) [class*="footer"] *,
    body:has(.tsa-flight-shell) [class*="newsletter"],
    body:has(.tsa-flight-shell) [class*="newsletter"] *,
    body:has(.tsa-flight-shell) [class*="subscribe"],
    body:has(.tsa-flight-shell) [class*="subscribe"] *{
        display:none!important;
        visibility:hidden!important;
        height:0!important;
        min-height:0!important;
        max-height:0!important;
        overflow:hidden!important;
        padding:0!important;
        margin:0!important;
        border:0!important;
    }
</style>
@endpush


@push('css')
<style id="tsa-flight-footer-css-only-hide-v2">
    body:has(.tsa-flight-shell) .bravo_footer,
    body:has(.tsa-flight-shell) .bravo_footer *,
    body:has(.tsa-flight-shell) .bravo-newsletter,
    body:has(.tsa-flight-shell) .bravo-newsletter *,
    body:has(.tsa-flight-shell) .bravo-subscribe-form,
    body:has(.tsa-flight-shell) .bravo-subscribe-form *,
    body:has(.tsa-flight-shell) .newsletter,
    body:has(.tsa-flight-shell) .newsletter *,
    body:has(.tsa-flight-shell) .footer,
    body:has(.tsa-flight-shell) .footer *,
    body:has(.tsa-flight-shell) footer,
    body:has(.tsa-flight-shell) footer *{
        display:none!important;
        visibility:hidden!important;
        height:0!important;
        min-height:0!important;
        max-height:0!important;
        overflow:hidden!important;
        padding:0!important;
        margin:0!important;
        border:0!important;
    }
</style>
@endpush
