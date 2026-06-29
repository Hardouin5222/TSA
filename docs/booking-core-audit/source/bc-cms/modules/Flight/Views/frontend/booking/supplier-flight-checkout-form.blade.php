<form method="POST" action="{{ route('booking.doCheckout') }}" class="booking-form supplier-flight-checkout-form" id="tsa-supplier-flight-checkout-form">
    @csrf
    <input type="hidden" name="code" value="{{ $booking->code }}">
    <input type="hidden" name="tsa_quote_uuid" value="{{ $booking->getMeta('tsa_supplier_quote_uuid') }}">
    <input type="hidden" name="how_to_pay" value="full">

    @php
    $quote = \Modules\Flight\Models\SupplierQuote::where('quote_uuid', $booking->getMeta('tsa_supplier_quote_uuid'))->first();
    $requirements = $quote ? ($quote->requirements_json ?: []) : [];
    $travellerReq = data_get($requirements, 'traveller', []);
    $travellerCount = max(1, (int) $booking->total_guests);
    @endphp

    <div class="form-section">
        <h4>{{ __('Contact Information') }}</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>{{ __('First name') }} <span class="text-danger">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label>{{ __('Last name') }} <span class="text-danger">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label>{{ __('Email') }} <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label>{{ __('Phone') }} <span class="text-danger">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="form-control" required>
            </div>
            <div class="col-md-12 form-group">
                <label>{{ __('Country') }} <span class="text-danger">*</span></label>
                <input type="text" name="country" value="{{ old('country', $user->country ?? 'TR') }}" class="form-control" required>
            </div>
        </div>
    </div>

    <div class="form-section mt-4">
        <h4>{{ __('Traveller Information') }}</h4>
        @for($i = 0; $i < $travellerCount; $i++)
            <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Traveller :number', ['number' => $i + 1]) }}</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ __('First name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="travellers[{{ $i }}][first_name]" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('Last name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="travellers[{{ $i }}][last_name]" class="form-control" required>
                    </div>
                    @if(data_get($travellerReq, 'birth_date'))
                    <div class="col-md-6 form-group">
                        <label>{{ __('Birth date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="travellers[{{ $i }}][birth_date]" class="form-control" required>
                    </div>
                    @endif
                    @if(data_get($travellerReq, 'gender'))
                    <div class="col-md-6 form-group">
                        <label>{{ __('Gender') }} <span class="text-danger">*</span></label>
                        <select name="travellers[{{ $i }}][gender]" class="form-control" required>
                            <option value="">{{ __('Select') }}</option>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </select>
                    </div>
                    @endif
                    @if(data_get($travellerReq, 'nationality'))
                    <div class="col-md-6 form-group">
                        <label>{{ __('Nationality') }} <span class="text-danger">*</span></label>
                        <input type="text" name="travellers[{{ $i }}][nationality]" class="form-control" maxlength="3" required>
                    </div>
                    @endif
                    @if(data_get($travellerReq, 'passport_number'))
                    <div class="col-md-6 form-group">
                        <label>{{ __('Passport number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="travellers[{{ $i }}][passport_number]" class="form-control" required>
                    </div>
                    @endif
                    @if(data_get($travellerReq, 'passport_expiry'))
                    <div class="col-md-6 form-group">
                        <label>{{ __('Passport expiry') }} <span class="text-danger">*</span></label>
                        <input type="date" name="travellers[{{ $i }}][passport_expiry]" class="form-control" required>
                    </div>
                    @endif
                </div>
            </div>
    </div>
    @endfor
    </div>

    <div class="form-section mt-4">
        <h4>{{ __('Billing') }}</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>{{ __('Invoice type') }}</label>
                <select name="invoice_type" class="form-control">
                    <option value="personal">{{ __('Personal') }}</option>
                    <option value="company">{{ __('Company') }}</option>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label>{{ __('Tax number') }}</label>
                <input type="text" name="tax_number" class="form-control">
            </div>
            <div class="col-md-12 form-group">
                <label>{{ __('Address') }}</label>
                <input type="text" name="address_line_1" class="form-control">
            </div>
        </div>
    </div>

    @include('Booking::frontend.booking.checkout-payment')

    <div class="form-group mt-3">
        <label>
            <input type="checkbox" name="term_conditions" value="1" required>
            {{ __('I have read and accept the terms and conditions') }}
        </label>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">
        {{ __('Continue to payment') }}
    </button>
    </form>

    @push('js')
<script>
    jQuery(function ($) {
        $(document).on('submit', '#tsa-supplier-flight-checkout-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('[type="submit"]');

            if ($form.data('loading')) {
                return false;
            }

            $form.data('loading', true);
            $button.prop('disabled', true);
            $form.find('.tsa-checkout-message').remove();

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.url) {
                        window.location.href = res.url;
                        return;
                    }

                    if (res.redirect) {
                        window.location.href = res.redirect;
                        return;
                    }

                    if (res.message) {
                        $form.prepend(
                            '<div class="alert alert-danger tsa-checkout-message">' +
                            res.message +
                            '</div>'
                        );
                    }
                },
                error: function (xhr) {
                    var message = @json(__('Checkout could not be completed. Please try again.'));

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                    }

                    $form.prepend(
                        '<div class="alert alert-danger tsa-checkout-message">' +
                        message +
                        '</div>'
                    );
                },
                complete: function () {
                    $form.data('loading', false);
                    $button.prop('disabled', false);
                }
            });

            return false;
        });
    });
</script>
@endpush