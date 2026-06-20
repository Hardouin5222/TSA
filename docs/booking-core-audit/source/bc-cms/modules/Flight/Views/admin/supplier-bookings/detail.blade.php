@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <h1 class="title-bar">{{ $page_title ?? __('Supplier Booking Detail') }}</h1>

        <div class="row">
            <div class="col-md-8">
                <div class="panel">
                    <div class="panel-title"><strong>{{ __('Booking') }}</strong></div>
                    <div class="panel-body">
                        <p><strong>{{ __('Booking Code') }}:</strong> {{ optional($row->booking)->code }}</p>
                        <p><strong>{{ __('Supplier') }}:</strong> {{ strtoupper($row->supplier_code) }}</p>
                        <p><strong>{{ __('Supplier Reference') }}:</strong> {{ $row->supplier_booking_reference ?: '-' }}</p>
                        <p><strong>{{ __('PNR') }}:</strong> {{ $row->pnr ?: '-' }}</p>
                        <p><strong>{{ __('Payment Status') }}:</strong> {{ $row->payment_status }}</p>
                        <p><strong>{{ __('Fulfillment Status') }}:</strong> {{ $row->fulfillment_status }}</p>
                        <p><strong>{{ __('Manual Review') }}:</strong> {{ $row->manual_review_required ? __('Yes') : __('No') }}</p>

                        <form method="POST" action="{{ route('flight.admin.supplier-bookings.retry', $row->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-warning" onclick="return confirm('{{ __('Retry supplier ticketing?') }}')">{{ __('Retry Ticketing') }}</button>
                        </form>
                        <form method="POST" action="{{ route('flight.admin.supplier-bookings.manual-review', $row->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-secondary">{{ __('Mark Manual Review') }}</button>
                        </form>
                    </div>
                </div>

                <div class="panel mt-4">
                    <div class="panel-title"><strong>{{ __('Operation Logs') }}</strong></div>
                    <div class="panel-body table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Operation') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Error') }}</th>
                                <th>{{ __('Duration') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at }}</td>
                                    <td>{{ $log->operation }}</td>
                                    <td>{{ $log->status }}</td>
                                    <td>{{ $log->normalized_error_code ?: '-' }}</td>
                                    <td>{{ $log->duration_ms ? $log->duration_ms.' ms' : '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-title"><strong>{{ __('Snapshot') }}</strong></div>
                    <div class="panel-body">
                        <pre style="max-height:650px;overflow:auto">{{ json_encode($row->snapshot_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
