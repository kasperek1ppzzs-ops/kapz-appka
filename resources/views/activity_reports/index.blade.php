@extends('layouts.app')

@section('title', 'Správa o pracovnej činnosti')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Správa o pracovnej činnosti KAPZ</h1>
            <p class="text-xs text-slate-500 mt-1">Mesačná správa o vykonaných aktivitách, kontrolách APZ a mediácii v komunitách za {{ $period->formatted_name }}</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($reports as $report)
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-3">
                        <h3 class="font-bold text-slate-900 text-sm">{{ $report->kapz->full_name }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase">
                            {{ $report->status }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 line-clamp-2">{{ $report->summary_text }}</p>
                </div>

                <div>
                    <a href="{{ route('activity_reports.pdf', $report->id) }}"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition inline-block">
                        📄 Stiahnuť Správu (PDF)
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
                Žiadne správy o činnosti neboli pre toto obdobie evidované.
            </div>
        @endforelse
    </div>
</div>
@endsection
