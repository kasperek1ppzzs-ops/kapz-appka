@extends('layouts.app')

@section('title', 'Cestovné Príkazy a Vyúčtovania')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Evidencia Cestovných Príkazov a Vyúčtovaní</h1>
            <p class="text-xs text-slate-500 mt-1">Generované cestovné príkazy, správy z ciest a náhrady</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-4">Číslo Príkazu</th>
                        <th class="p-4">KAPZ</th>
                        <th class="p-4">Termín a Trasa</th>
                        <th class="p-4">Účel Cesty</th>
                        <th class="p-4 text-right">Vyúčtovanie</th>
                        <th class="p-4 text-right">Akcia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 font-bold text-blue-600">{{ $order->order_number }}</td>
                            <td class="p-4 font-semibold text-slate-900">{{ $order->kapz->full_name }}</td>
                            <td class="p-4">
                                {{ date('d.m.Y H:i', strtotime($order->departure_datetime)) }}<br>
                                <span class="text-slate-500">{{ $order->departure_place }} &rarr; {{ $order->destination_place }}</span>
                            </td>
                            <td class="p-4">{{ $order->purpose }}</td>
                            <td class="p-4 text-right font-black text-slate-900">
                                {{ $order->expense ? number_format($order->expense->final_balance, 2) . ' €' : '-' }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('travel.orders.pdf', $order->id) }}"
                                    class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition">
                                    📄 Stiahnuť PDF
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
