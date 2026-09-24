@extends('layouts.app')

@section('title', 'Správa KAPZ Profilov')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Správa Profilov KAPZ (Koordinátori Asistentov Podpory Zdravia)</h1>
            <p class="text-xs text-slate-500 mt-1">Zoznam koordinátorov, osobné čísla, pracovný úväzok a obvody pôsobnosti</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3">Osobné Číslo</th>
                        <th class="p-3">Meno a Priezvisko</th>
                        <th class="p-3">Pôsobnosť / Lokalita</th>
                        <th class="p-3">Úväzok</th>
                        <th class="p-3">Email Používateľa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($kapzs as $kapz)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-900">{{ $kapz->personal_number }}</td>
                            <td class="p-3 font-bold text-blue-700">{{ $kapz->full_name }}</td>
                            <td class="p-3 font-medium">{{ $kapz->scope }}</td>
                            <td class="p-3 font-semibold">{{ $kapz->employment_ratio * 100 }} %</td>
                            <td class="p-3 text-slate-600">{{ $kapz->user?->email }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
