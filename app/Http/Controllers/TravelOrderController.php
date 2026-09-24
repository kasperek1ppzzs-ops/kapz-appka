<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TravelOrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $orders = TravelOrder::with(['kapz', 'report', 'expense'])
            ->when(!$user->isSupervisor(), fn ($q) => $q->where('kapz_id', $user->kapzProfile?->id))
            ->orderBy('id', 'desc')
            ->get();
        return view('travel.orders_index', compact('orders'));
    }

    public function show(TravelOrder $order)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load(['kapz', 'travelPlanItem', 'report', 'expense']);
        return view('travel.order_detail', compact('order'));
    }

    public function downloadPdf(TravelOrder $order, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $pdf = $pdfGenerator->generateTravelOrderPdf($order);
        return $pdf->download('CESTOVNY_PRIKAZ_' . $order->order_number . '.pdf');
    }
}
