@extends('admin.layouts.app')

@section('content')
@php
    $booking = $row->booking;
    $quote = $row->quote;
    $offer = $quote ? $quote->offer : null;

    $isFinalized = in_array($row->fulfillment_status, ['ticket_issued', 'booking_confirmed'], true);

    $badge = function ($status) {
        $status = (string) $status;

        if (in_array($status, ['ticket_issued', 'booking_confirmed', 'confirmed', 'paid', 'success'], true)) {
            return 'label label-success';
        }

        if (in_array($status, ['payment_pending', 'ticketing_in_progress', 'manual_retry_queued', 'processing'], true)) {
            return 'label label-warning';
        }

        if (in_array($status, ['manual_review_required', 'failed', 'cancelled', 'refunded'], true)) {
            return 'label label-danger';
        }

        return 'label label-default';
    };

    $prettyJson = function ($value) {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return json_encode($value ?: new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };

    $ticketNumbers = $row->ticket_numbers_json ?? [];
    if (is_string($ticketNumbers)) {
        $decodedTickets = json_decode($ticketNumbers, true);
        $ticketNumbers = json_last_error() === JSON_ERROR_NONE ? $decodedTickets : [$ticketNumbers];
    }

    $supplierBookResponse = data_get($row->snapshot_json, 'supplier_book_response', []);
@endphp

<div class="container-fluid">
    <h1 class="title-bar">{{ $page_title ?? __('Supplier Booking Detail') }}</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-8">
            
        <div class="panel">
            <div class="panel-title">
                <strong>{{ __('Payment Transaction') }}</strong>
            </div>
            <div class="panel-body">
                @php
                    $booking = $row->booking;
                    $payment = $booking ? $booking->payment : null;
                    $paymentLogsRaw = $payment ? $payment->logs : null;
                    $paymentLogsDecoded = is_string($paymentLogsRaw) ? json_decode($paymentLogsRaw, true) : null;
                @endphp

                @if($payment)
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>{{ __('Payment ID') }}:</strong> #{{ $payment->id }}</p>
                            <p><strong>{{ __('Payment Code') }}:</strong> {{ $payment->code ?: '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>{{ __('Gateway') }}:</strong> {{ $payment->payment_gateway ?: '-' }}</p>
                            <p><strong>{{ __('Status') }}:</strong> <span class="badge badge-success">{{ $payment->status ?: '-' }}</span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>{{ __('Amount') }}:</strong> {{ $payment->amount ?: '0' }} {{ $payment->currency ?: '' }}</p>
                            <p><strong>{{ __('Created At') }}:</strong> {{ $payment->created_at ?: '-' }}</p>
                        </div>
                    </div>

                    <hr>

                    <p><strong>{{ __('Gateway Logs / Reference') }}</strong></p>

                    @if(is_array($paymentLogsDecoded))
                        <pre style="white-space: pre-wrap; background:#f8f9fa; padding:12px; border:1px solid #eee; border-radius:4px;">{{ json_encode($paymentLogsDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @elseif($paymentLogsRaw)
                        <pre style="white-space: pre-wrap; background:#f8f9fa; padding:12px; border:1px solid #eee; border-radius:4px;">{{ $paymentLogsRaw }}</pre>
                    @else
                        <p class="text-muted">{{ __('No payment logs found.') }}</p>
                    @endif
                @else
                    <div class="alert alert-warning mb-0">
                        {{ __('No payment transaction record is linked to this booking yet.') }}
                    </div>
                @endif
            </div>
        </div>

<div class="panel">
                <div class="panel-title"><strong>{{ __('Supplier Ticketing Status') }}</strong></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('Booking Code') }}:</strong> {{ optional($booking)->code ?: '-' }}</p>
                            <p><strong>{{ __('Booking Status') }}:</strong>
                                <span class="{{ $badge(optional($booking)->status) }}">{{ optional($booking)->status ?: '-' }}</span>
                            </p>
                            <p><strong>{{ __('Gateway') }}:</strong> {{ optional($booking)->gateway ?: '-' }}</p>
                            <p><strong>{{ __('Paid') }}:</strong> {{ optional($booking)->paid ?: '-' }}</p>
                            <p><strong>{{ __('Total') }}:</strong> {{ optional($booking)->currency }} {{ optional($booking)->total }}</p>
                        </div>

                        <div class="col-md-6">
                            <p><strong>{{ __('Supplier') }}:</strong> {{ $row->supplier_code ?: '-' }}</p>
                            <p><strong>{{ __('Payment Status') }}:</strong>
                                <span class="{{ $badge($row->payment_status) }}">{{ $row->payment_status ?: '-' }}</span>
                            </p>
                            <p><strong>{{ __('Fulfillment Status') }}:</strong>
                                <span class="{{ $badge($row->fulfillment_status) }}">{{ $row->fulfillment_status ?: '-' }}</span>
                            </p>
                            <p><strong>{{ __('Manual Review') }}:</strong>
                                @if($row->manual_review_required)
                                    <span class="label label-danger">{{ __('Yes') }}</span>
                                @else
                                    <span class="label label-success">{{ __('No') }}</span>
                                @endif
                            </p>
                            <p><strong>{{ __('Supplier Reference') }}:</strong> {{ $row->supplier_booking_reference ?: '-' }}</p>
                            <p><strong>{{ __('PNR') }}:</strong> {{ $row->pnr ?: '-' }}</p>
                        </div>
                    </div>

                    <hr>

                    <p><strong>{{ __('Ticket Numbers') }}:</strong></p>
                    @if(!empty($ticketNumbers))
                        @foreach($ticketNumbers as $ticketNumber)
                            <span class="label label-info" style="font-size:13px;margin-right:5px;">{{ $ticketNumber }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">-</span>
                    @endif

                    <hr>

                    @if($isFinalized)
                        <div class="alert alert-success mt-3 mb-0">
                            {{ __('Ticketing completed. Retry and manual review actions are locked to prevent duplicate supplier booking.') }}
                        </div>
                    @else
                        <form method="POST" action="{{ route('flight.admin.supplier-bookings.retry', $row->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-warning" onclick="return confirm('{{ __('Retry supplier ticketing?') }}')">
                                {{ __('Retry Ticketing') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('flight.admin.supplier-bookings.manual-review', $row->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-secondary" onclick="return confirm('{{ __('Mark this booking as manual review?') }}')">
                                {{ __('Mark Manual Review') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="panel mt-4">
                <div class="panel-title"><strong>{{ __('Quote / Offer') }}</strong></div>
                <div class="panel-body">
                    <p><strong>{{ __('Quote UUID') }}:</strong> {{ $row->quote_uuid ?: optional($quote)->quote_uuid ?: '-' }}</p>
                    <p><strong>{{ __('Quote ID') }}:</strong> {{ $row->quote_id ?: '-' }}</p>
                    <p><strong>{{ __('Confirmed Total') }}:</strong> {{ optional($quote)->confirmed_currency }} {{ optional($quote)->confirmed_total_amount }}</p>
                    <p><strong>{{ __('Price Changed') }}:</strong> {{ optional($quote)->price_changed ? __('Yes') : __('No') }}</p>
                    <p><strong>{{ __('Quote Status') }}:</strong> {{ optional($quote)->status ?: '-' }}</p>
                    <p><strong>{{ __('Offer UUID') }}:</strong> {{ optional($offer)->offer_uuid ?: '-' }}</p>
                    <p><strong>{{ __('Route') }}:</strong> {{ optional($offer)->origin ?: '-' }} → {{ optional($offer)->destination ?: '-' }}</p>
                </div>
            </div>

            <div class="panel mt-4">
                <div class="panel-title"><strong>{{ __('Operation Logs') }}</strong></div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Operation') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Error') }}</th>
                                <th>{{ __('Duration') }}</th>
                                <th>{{ __('Correlation') }}</th>
                                <th>{{ __('Payload') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at }}</td>
                                    <td>{{ $log->operation }}</td>
                                    <td><span class="{{ $badge($log->status) }}">{{ $log->status }}</span></td>
                                    <td>{{ $log->normalized_error_code ?: '-' }}</td>
                                    <td>{{ $log->duration_ms ? $log->duration_ms . ' ms' : '-' }}</td>
                                    <td>{{ $log->correlation_id ?: '-' }}</td>
                                    <td>
                                        <details>
                                            <summary>{{ __('View') }}</summary>
                                            <strong>{{ __('Request') }}</strong>
                                            <pre style="max-height:260px;overflow:auto;">{{ $prettyJson($log->request_json) }}</pre>
                                            <strong>{{ __('Response') }}</strong>
                                            <pre style="max-height:260px;overflow:auto;">{{ $prettyJson($log->response_json) }}</pre>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ __('No operation log yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $logs->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel">
                <div class="panel-title"><strong>{{ __('Supplier Book Response') }}</strong></div>
                <div class="panel-body">
                    <pre style="max-height:420px;overflow:auto;">{{ $prettyJson($supplierBookResponse) }}</pre>
                </div>
            </div>

            <div class="panel mt-4">
                <div class="panel-title"><strong>{{ __('Supplier Booking Snapshot') }}</strong></div>
                <div class="panel-body">
                    <pre style="max-height:650px;overflow:auto;">{{ $prettyJson($row->snapshot_json) }}</pre>
                </div>
            </div>

            <div class="panel mt-4">
                <div class="panel-title"><strong>{{ __('Quote Payload') }}</strong></div>
                <div class="panel-body">
                    <pre style="max-height:650px;overflow:auto;">{{ $prettyJson(optional($quote)->payload_json) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
