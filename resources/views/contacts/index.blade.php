@extends('layouts.app')

@section('title', 'Zoznam Kontaktov - KAPZ & APZ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-slate-900">Zoznam Kontaktov</h1>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase tracking-wider">
                    {{ count($allContacts) }} osôb / {{ count($groupedContacts) }} oddelení
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Oficiálny organizačný zoznam kontaktov koordinátorov KAPZ, expertov, manažérov a oddelení</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('contacts.pdf', request()->query()) }}"
                class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-2">
                <span>📄</span>
                <span>Stiahnuť Oficiálne PDF</span>
            </a>
            <a href="{{ route('contacts.export_csv') }}"
                class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-2">
                <span>📊</span>
                <span>Export do Excel / CSV</span>
            </a>
        </div>
    </div>

    <!-- Interactive Filter & Search Box -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <form action="{{ route('contacts.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <!-- Search Box -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-600 mb-1">Rýchle vyhľadávanie v kontaktoch</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Hľadať podľa mena, telefónu, emailu, funkcie alebo mesta..."
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    <span class="absolute left-3 top-2.5 text-slate-400 text-sm">🔍</span>
                </div>
            </div>

            <!-- Department / Section Filter -->
            <div class="md:col-span-3">
                <label class="block text-xs font-bold text-slate-600 mb-1">Sekcia / Oddelenie</label>
                <select name="section" onchange="this.form.submit()"
                    class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700">
                    <option value="">Všetky oddelenia a sekcie</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>
                            {{ $sec }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Scope / City Filter -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1">Lokalita / Pôsobnosť</label>
                <select name="scope" onchange="this.form.submit()"
                    class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700">
                    <option value="">Všetky lokality</option>
                    @foreach($scopes as $sc)
                        <option value="{{ $sc }}" {{ request('scope') == $sc ? 'selected' : '' }}>
                            {{ $sc }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action -->
            <div class="md:col-span-1 flex items-end">
                @if(request()->hasAny(['search', 'section', 'scope']))
                    <a href="{{ route('contacts.index') }}"
                        class="w-full py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold text-center transition">
                        Reset
                    </a>
                @else
                    <button type="submit"
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                        Filtrovať
                    </button>
                @endif
            </div>
        </form>

        <!-- Quick Filter Category Badges -->
        <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t border-slate-100 text-xs">
            <span class="font-bold text-slate-400 mr-1">Rýchly filter:</span>
            <a href="{{ route('contacts.index') }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ !request('section') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Všetci ({{ $totalCount }})
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Koordinátori asistentov podpory zdravia']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Koordinátori asistentov podpory zdravia' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                KAPZ Koordinátori ({{ $kapzCount }})
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Experti pre terén']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Experti pre terén' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                Experti pre terén ({{ $expertCount }})
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Regionálni manažéri podpory zdravia']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Regionálni manažéri podpory zdravia' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                Regionálni manažéri
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Ekonomické oddelenie']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Ekonomické oddelenie' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Ekonomické oddelenie
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Oddelenie pre terén']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Oddelenie pre terén' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' }}">
                Oddelenie pre terén
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Oddelenie pre metodiku']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Oddelenie pre metodiku' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                Metodika
            </a>
            <a href="{{ route('contacts.index', ['section' => 'Oddelenie vzdelávania a rozvoja ľudských zdrojov']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'Oddelenie vzdelávania a rozvoja ľudských zdrojov' ? 'bg-cyan-600 text-white' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100' }}">
                Vzdelávanie
            </a>
            <a href="{{ route('contacts.index', ['section' => 'IT oddelenie']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'IT oddelenie' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                IT oddelenie
            </a>
            <a href="{{ route('contacts.index', ['section' => 'PR oddelenie']) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition {{ request('section') == 'PR oddelenie' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                PR oddelenie
            </a>
        </div>
    </div>

    <!-- Structured Departmental Tables (Matching PDF Layout) -->
    <div class="space-y-6">
        @forelse($groupedContacts as $sectionTitle => $contacts)
            @php
                $isKapz = ($sectionTitle === 'Koordinátori asistentov podpory zdravia');
                $isDirector = stripos($sectionTitle, 'Riaditeľka') !== false;
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Section Header Banner matching PDF -->
                <div class="px-5 py-2.5 font-bold text-xs flex items-center justify-between border-b border-slate-300
                    {{ $isDirector ? 'bg-amber-200 text-amber-950 font-black' : 'bg-[#c6d9f1] text-[#1e3a8a]' }}">
                    <div class="flex items-center space-x-2">
                        <span>📁</span>
                        <span>{{ $sectionTitle }}</span>
                    </div>
                    <span class="text-[11px] font-bold opacity-80">
                        {{ count($contacts) }} kontaktov
                    </span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 text-[11px] font-bold border-b border-slate-200">
                                <th class="py-2.5 px-3 w-12 text-center border-r border-slate-200">P. č.</th>
                                <th class="py-2.5 px-3 w-1/5 border-r border-slate-200">Priezvisko a meno</th>
                                <th class="py-2.5 px-3 w-1/5 border-r border-slate-200">E-mail</th>
                                <th class="py-2.5 px-3 w-36 text-center border-r border-slate-200">Tel. číslo</th>
                                <th class="py-2.5 px-3 w-36 text-center border-r border-slate-200">Pôsobnosť/lokalita</th>
                                <th class="py-2.5 px-3 {{ $isKapz ? 'border-r border-slate-200' : '' }}">Pracovná pozícia</th>
                                @if($isKapz)
                                    <th class="py-2.5 px-3 w-1/5">Príslušnosť k Expertovi pre terén</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            @foreach($contacts as $contact)
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="py-2 px-3 text-center font-bold text-slate-500 border-r border-slate-200 bg-slate-50/40">
                                        {{ $contact->order_num ?: $loop->iteration }}
                                    </td>
                                    <td class="py-2 px-3 font-bold text-slate-900 border-r border-slate-200">
                                        {{ $contact->name }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        @if($contact->email)
                                            <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center space-x-1">
                                                <span>✉️</span>
                                                <span class="truncate">{{ $contact->email }}</span>
                                            </a>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-center border-r border-slate-200 font-mono">
                                        @if($contact->phone)
                                            <a href="tel:{{ preg_replace('/\s+/', '', $contact->phone) }}" class="text-slate-800 hover:text-blue-600 font-semibold flex items-center justify-center space-x-1">
                                                <span>📞</span>
                                                <span>{{ $contact->phone }}</span>
                                            </a>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-center font-medium text-slate-700 border-r border-slate-200">
                                        {{ $contact->scope ?: '-' }}
                                    </td>
                                    <td class="py-2 px-3 text-slate-800 {{ $isKapz ? 'border-r border-slate-200' : '' }}">
                                        {{ $contact->position }}
                                    </td>
                                    @if($isKapz)
                                        <td class="py-2 px-3 text-slate-700 font-medium">
                                            {{ $contact->region_expert ?: '-' }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400 text-xs shadow-sm">
                Nenašli sa žiadne kontakty vyhovujúce zadaným filtrom.
            </div>
        @endforelse
    </div>
</div>
@endsection
