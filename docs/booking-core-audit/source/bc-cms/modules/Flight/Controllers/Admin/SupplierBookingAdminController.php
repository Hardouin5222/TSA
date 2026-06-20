<?php

namespace Modules\Flight\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Flight\Events\SupplierPaymentConfirmed;
use Modules\Flight\Models\SupplierBooking;
use Modules\Flight\Models\SupplierOperationLog;

class SupplierBookingAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierBooking::with(['booking', 'quote'])->latest();

        if ($request->filled('status')) {
            $query->where('fulfillment_status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('pnr', 'like', "%{$q}%")
                    ->orWhere('supplier_booking_reference', 'like', "%{$q}%")
                    ->orWhereHas('booking', function ($bookingQuery) use ($q) {
                        $bookingQuery->where('code', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        return view('Flight::admin.supplier-bookings.index', [
            'rows' => $query->paginate(30),
            'page_title' => __('Supplier Flight Bookings'),
        ]);
    }

    public function detail(int $id)
    {
        $row = SupplierBooking::with(['booking', 'quote.offer'])->findOrFail($id);
        $logs = SupplierOperationLog::where('booking_id', $row->booking_id)->latest()->paginate(30);

        return view('Flight::admin.supplier-bookings.detail', [
            'row' => $row,
            'logs' => $logs,
            'page_title' => __('Supplier Booking Detail'),
        ]);
    }

    public function retryTicketing(int $id)
    {
        $row = SupplierBooking::with('booking')->findOrFail($id);

        if (in_array($row->fulfillment_status, ['ticket_issued', 'booking_confirmed'], true)) {
            return back()->with('error', __('This supplier booking is already ticketed or confirmed. Retry is blocked to prevent duplicate ticketing.'));
        }

        if (!$row->booking) {
            return back()->with('error', __('Booking not found'));
        }

        $row->fulfillment_status = 'manual_retry_queued';
        $row->manual_review_required = false;
        $row->save();

        event(new SupplierPaymentConfirmed($row->booking_id, 'admin_retry', ['admin_retry' => true]));

        return back()->with('success', __('Ticketing retry has been queued.'));
    }

    public function markManualReview(int $id)
    {
        $row = SupplierBooking::findOrFail($id);

        if (in_array($row->fulfillment_status, ['ticket_issued', 'booking_confirmed'], true)) {
            return back()->with('error', __('This supplier booking is already ticketed or confirmed. Manual review cannot override a completed ticket.'));
        }

        $row->manual_review_required = true;
        $row->fulfillment_status = 'manual_review_required';
        $row->save();

        if ($row->booking) {
            $row->booking->status = Booking::PROCESSING;
            $row->booking->addMeta('tsa_fulfillment_status', 'manual_review_required');
            $row->booking->save();
        }

        return back()->with('success', __('Booking marked as manual review.'));
    }
}
