@extends('layouts.app')

@section('title', 'Správa APZ Profilov')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Správa Profilov APZ (Asistenti Podpory Zdravia)</h1>
            <p class="text-xs text-slate-500 mt-1">Zoznam terénnych asistentov podpory zdravia v komunitách MRK</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3">Osobné Číslo</th>
                        <th class="p-3">Meno a Priezvisko</th>
                        <th class="p-3">Pôsobnosť / Komunita</th>
                        <th class="p-3">Úväzok</th>
                        <th class="p-3">Aktuálne Priradený KAPZ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($apzs as $apz)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-900">{{ $apz->personal_number }}</td>
                            <td class="p-3 font-bold text-emerald-700">{{ $apz->full_name }}</td>
                            <td class="p-3 font-medium">{{ $apz->community_scope }}</td>
                            <td class="p-3 font-semibold">{{ $apz->employment_ratio * 100 }} %</td>
                            <td class="p-3">
                                @php
                                    $activeAssignment = $apz->assignments->where('valid_to', null)->first();
                                @endphp
                                @if($activeAssignment)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                        👤 {{ $activeAssignment->kapz->full_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-italic">Nepriradený</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
