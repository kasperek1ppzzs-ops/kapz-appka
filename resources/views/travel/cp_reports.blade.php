@extends('layouts.app')

@section('title', 'Správy z pracovných ciest')

@section('content')
@php $editable = $order->status === 'DRAFT'; @endphp
<div class="space-y-5">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Správy z pracovných ciest k CP č. {{ $order->order_number }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $order->kapz->full_name }} · {{ $order->reportingPeriod->formatted_name }} · {{ $days->count() }} dní s pracovnou cestou</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('travel.cp.index', ['period_id' => $order->reporting_period_id, 'kapz_id' => $order->kapz_id]) }}" class="px-4 py-2 bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold">← Cestovný príkaz</a>
            <a href="{{ route('travel.cp.reports_pdf', $order) }}" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">📄 PDF Správy z pracovných ciest</a>
            <a href="{{ route('travel.cp.generator_pdf', $order) }}" class="px-4 py-2 bg-slate-700 text-white rounded-xl text-xs font-bold">📄 PDF Správa zo služobnej cesty (GENERATOR)</a>
        </div>
    </div>

    <form method="POST" action="{{ route('travel.cp.reports.save', $order) }}" class="space-y-4">
        @csrf
        <fieldset {{ $editable ? '' : 'disabled' }} class="space-y-4">
        @foreach($days as $d)
            @php $key = $d['date']->toDateString(); @endphp
            <div class="bg-white p-5 rounded-2xl border border-slate-200 text-xs space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
                    <div><span class="text-slate-500">c) Miesto pracovnej cesty:</span> <strong>{{ $d['places'] }}</strong></div>
                    <div><span class="text-slate-500">d) Dátum pracovnej cesty:</span> <strong>{{ $d['date']->format('d.m.Y') }} – {{ $d['date']->format('d.m.Y') }}</strong></div>
                    <div class="md:col-span-2"><span class="text-slate-500">e) Účel cesty v CP:</span> <strong>{{ $d['purpose'] ?: '—' }}</strong></div>
                </div>
                <label class="block">
                    <span class="font-bold text-slate-700">Správa z pracovnej cesty</span>
                    <textarea name="days[{{ $key }}][report_text]" rows="4" class="w-full mt-1 p-2 border border-slate-300 rounded">{{ $d['report_text'] }}</textarea>
                </label>
                <label class="block">
                    <span class="font-bold text-slate-700">Závery/Odporúčania (GENERATOR)</span>
                    <span class="text-slate-400">– predvolene podľa účelu cesty</span>
                    <textarea name="days[{{ $key }}][conclusion]" rows="2" placeholder="{{ $d['default_conclusion'] }}" class="w-full mt-1 p-2 border border-slate-300 rounded">{{ $d['conclusion'] !== $d['default_conclusion'] ? $d['conclusion'] : '' }}</textarea>
                </label>
            </div>
        @endforeach
        @if($editable && $days->isNotEmpty())
            <div class="flex justify-end">
                <button class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">💾 Uložiť správy</button>
            </div>
        @endif
        </fieldset>
    </form>
</div>
@endsection
