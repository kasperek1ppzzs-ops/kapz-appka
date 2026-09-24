@extends('layouts.app')

@section('title', 'Systémové Bezpečnostné Logy (Audit Logs)')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h1 class="text-xl font-bold text-slate-900">Systémové Auditné Logy (Bezpečnosť & Sledovanie Zmien)</h1>
        <p class="text-xs text-slate-500 mt-1">Nezmanipulovateľný záznam o prihláseniach, úpravách dochádzky, schvaľovaní a uzamykaní období</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3 w-20">ID</th>
                        <th class="p-3 w-36">Čas</th>
                        <th class="p-3">Používateľ</th>
                        <th class="p-3">Akcia / Operácia</th>
                        <th class="p-3">IP Adresa</th>
                        <th class="p-3">Payload (Detaily)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($logs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-mono text-slate-500">#{{ $log->id }}</td>
                            <td class="p-3 text-slate-600">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td class="p-3 font-bold text-slate-900">{{ $log->user ? $log->user->name : 'Systém' }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="p-3 font-mono text-slate-500">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            <td class="p-3 font-mono text-[10px] text-slate-600 truncate max-w-xs">{{ $log->payload }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
