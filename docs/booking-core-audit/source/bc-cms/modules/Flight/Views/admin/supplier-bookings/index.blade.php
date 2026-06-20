@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="title-bar">{{ $page_title ?? __('Supplier Flight Bookings') }}</h1>
        </div>

        <form method="GET" class="filter-div d-flex gap-2 mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Booking code, PNR, email, phone') }}">
            <select name="status" class="form-control">
                <option value="">{{ __('All statuses') }}</option>
                @foreach(['payment_pending','payment_paid_ticketing_queued','ticketing_in_progress','booking_confirmed','ticket_issued','manual_review_required','payment_failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">{{ __('Filter') }}</button>
        </form>

        <div class="panel">
            <div class="panel-body">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>{{ __('Booking') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('PNR') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th>{{ __('Fulfillment') }}</th>
                        <th>{{ __('Manual') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ optional($row->booking)->code }}</td>
                            <td>{{ strtoupper($row->supplier_code) }}</td>
                            <td>{{ $row->pnr ?: '-' }}</td>
                            <td>{{ $row->payment_status }}</td>
                            <td>{{ $row->fulfillment_status }}</td>
                            <td>{!! $row->manual_review_required ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-success">No</span>' !!}</td>
                            <td>{{ $row->created_at }}</td>
                            <td><a class="btn btn-sm btn-info" href="{{ route('flight.admin.supplier-bookings.detail', $row->id) }}">{{ __('Detail') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8">{{ __('No records found') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $rows->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
