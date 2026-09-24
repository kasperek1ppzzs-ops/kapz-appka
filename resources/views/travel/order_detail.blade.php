@extends('layouts.app')

@section('title', 'Cestovný príkaz ' . $order->order_number)

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Cestovný príkaz č. {{ $order->order_number }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $order->kapz->full_name }} ({{ $order->kapz->personal_number }}) · {{ $order->kapz->scope }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('travel.orders') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold border border-slate-200">← Späť na zoznam</a>
            <a href="{{ route('travel.orders.pdf', $order) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">📄 Stiahnuť PDF</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Údaje pracovnej cesty</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-xs">
            <dt class="font-bold text-slate-500">Začiatok cesty</dt>
            <dd>{{ \Carbon\Carbon::parse($order->departure_datetime)->format('d.m.Y H:i') }} – {{ $order->departure_place }}</dd>
            <dt class="font-bold text-slate-500">Koniec cesty</dt>
            <dd>{{ $order->arrival_datetime ? \Carbon\Carbon::parse($order->arrival_datetime)->format('d.m.Y H:i') : '' }} – {{ $order->destination_place }}</dd>
            <dt class="font-bold text-slate-500">Účel cesty</dt>
            <dd>{{ $order->purpose }}</dd>
            <dt class="font-bold text-slate-500">Dopravný prostriedok</dt>
            <dd>{{ $order->transport_means }}</dd>
            <dt class="font-bold text-slate-500">Preddavok</dt>
            <dd>{{ number_format($order->advance_amount, 2, ',', ' ') }} €</dd>
            @if($order->travelPlanItem)
                <dt class="font-bold text-slate-500">Položka plánu</dt>
                <dd>{{ \Carbon\Carbon::parse($order->travelPlanItem->trip_date)->format('d.m.Y') }} · {{ $order->travelPlanItem->destination_location }}</dd>
            @endif
        </dl>
    </div>

    @if($order->report)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Správa z pracovnej cesty</h2>
            <p class="text-xs text-slate-800">{{ $order->report->summary_of_activities }}</p>
            @if($order->report->outcomes)
                <p class="text-xs text-slate-600 mt-2"><strong>Závery:</strong> {{ $order->report->outcomes }}</p>
            @endif
        </div>
    @endif

    @if($order->expense)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Vyúčtovanie</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs max-w-md">
                <dt class="font-bold text-slate-500">Km spolu</dt><dd class="text-right">{{ number_format($order->expense->total_km, 2, ',', ' ') }}</dd>
                <dt class="font-bold text-slate-500">Náhrada za km</dt><dd class="text-right">{{ number_format($order->expense->total_km_compensation, 2, ',', ' ') }} €</dd>
                <dt class="font-bold text-slate-500">Stravné</dt><dd class="text-right">{{ number_format($order->expense->diem_compensation, 2, ',', ' ') }} €</dd>
                <dt class="font-bold text-slate-500">Nocľažné</dt><dd class="text-right">{{ number_format($order->expense->accommodation_costs, 2, ',', ' ') }} €</dd>
                <dt class="font-bold text-slate-500">Vedľajšie výdavky</dt><dd class="text-right">{{ number_format($order->expense->other_expenses, 2, ',', ' ') }} €</dd>
                <dt class="font-bold text-slate-500">Preddavok</dt><dd class="text-right">{{ number_format($order->expense->advance_deducted, 2, ',', ' ') }} €</dd>
                <dt class="font-black text-slate-800">Doplatok / preplatok</dt><dd class="text-right font-black">{{ number_format($order->expense->final_balance, 2, ',', ' ') }} €</dd>
            </dl>
        </div>
    @endif
</div>
@endsection
