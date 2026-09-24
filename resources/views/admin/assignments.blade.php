@extends('layouts.app')

@section('title', 'Priradenie APZ ku KAPZ')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h1 class="text-xl font-bold text-slate-900">Historické a Aktuálne Priradenie APZ ku KAPZ</h1>
        <p class="text-xs text-slate-500 mt-1">Sledovanie časového intervalu platnosti priradenia (`valid_from` &rarr; `valid_to`)</p>
    </div>

    <!-- Form to create new assignment -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">+ Vytvoriť / Zmeniť Priradenie APZ Asistenta</h3>
        <form action="{{ route('admin.assignments.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">APZ Asistent</label>
                <select name="apz_id" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-semibold">
                    @foreach($apzs as $a)
                        <option value="{{ $a->id }}">{{ $a->full_name }} ({{ $a->personal_number }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Priradiť ku KAPZ</label>
                <select name="kapz_id" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-semibold">
                    @foreach($kapzs as $k)
                        <option value="{{ $k->id }}">{{ $k->full_name }} ({{ $k->personal_number }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Platnosť od (Dátum)</label>
                <input type="date" name="valid_from" value="{{ date('Y-m-01') }}" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Platnosť do (Voliteľné)</label>
                <input type="date" name="valid_to" class="w-full p-2.5 border border-slate-300 rounded-xl text-xs">
            </div>

            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="py-2.5 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    💾 Uložiť Priradenie
                </button>
            </div>
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50 font-bold text-xs text-slate-700 uppercase">
            História Priradení APZ ku KAPZ
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3">APZ Meno</th>
                        <th class="p-3">Priradený KAPZ</th>
                        <th class="p-3">Platnosť od</th>
                        <th class="p-3">Platnosť do</th>
                        <th class="p-3">Stav</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($assignments as $as)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-emerald-700">{{ $as->apz->full_name }}</td>
                            <td class="p-3 font-bold text-blue-700">{{ $as->kapz->full_name }}</td>
                            <td class="p-3 font-semibold">{{ date('d.m.Y', strtotime($as->valid_from)) }}</td>
                            <td class="p-3 font-semibold">{{ $as->valid_to ? date('d.m.Y', strtotime($as->valid_to)) : 'Neobmedzene (Aktívne)' }}</td>
                            <td class="p-3">
                                @if(!$as->valid_to || strtotime($as->valid_to) >= time())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">Aktívne</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 uppercase">Ukončené</span>
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
