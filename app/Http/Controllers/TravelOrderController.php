<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;

class TravelOrderController extends Controller
{
    public function index()
    {
        $orders = TravelOrder::with(['kapz', 'report', 'expense'])->orderBy('id', 'desc')->get();
        return view('travel.orders_index', compact('orders'));
    }

    public function show(TravelOrder $order)
    {
        $order->load(['kapz', 'travelPlanItem', 'report', 'expense']);
        return view('travel.order_detail', compact('order'));
    }

    public function downloadPdf(TravelOrder $order, PdfGeneratorService $pdfGenerator)
    {
        $pdf = $pdfGenerator->generateTravelOrderPdf($order);
        return $pdf->download('CESTOVNY_PRIKAZ_' . $order->order_number . '.pdf');
    }
}
