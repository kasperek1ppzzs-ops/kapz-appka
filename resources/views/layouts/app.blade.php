<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KAPZ / APZ Dokumentačný Systém')</title>
    <!-- Google Fonts & Tailwind CDN for UI Excellence -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-slate-900 text-white shadow-lg border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-bold text-lg shadow-md shadow-blue-500/20">
                            K
                        </div>
                        <div>
                            <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">KAPZ / APZ</span>
                            <span class="text-xs block text-slate-400 font-medium">Informačný Systém Dokumentácie</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                @auth
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('attendance.kapz') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('attendance.kapz*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Dochádzka KAPZ
                    </a>
                    <a href="{{ route('attendance.apz') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('attendance.apz*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Dochádzka APZ
                    </a>
                    <a href="{{ route('travel.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('travel.index*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Týždenný Plán
                    </a>
                    <a href="{{ route('travel.orders') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('travel.orders*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Cestovné Príkazy
                    </a>
                    <a href="{{ route('statements.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('statements.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Prehlásenia
                    </a>
                    <a href="{{ route('knihy.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('knihy.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        KNIHY
                    </a>
                    <a href="{{ route('activity_reports.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('activity_reports.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        Správa o činnosti
                    </a>
                    <a href="{{ route('calendar.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('calendar.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        📅 Kalendár
                    </a>
                    <a href="{{ route('contacts.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('contacts.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        👥 Kontakty
                    </a>

                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('reports.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            📊 Reporty
                        </a>
                        <a href="{{ route('admin.assignments.index') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->routeIs('admin.*') ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            ⚙️ Admin
                        </a>
                    @endif
                </div>

                <!-- User Profile / Logout -->
                <div class="flex items-center space-x-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-white">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-blue-400 uppercase font-bold tracking-wider">{{ Auth::user()->role }} @if(Auth::user()->personal_number)({{ Auth::user()->personal_number }})@endif</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-700 transition">
                            Odhlásiť
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content Body -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium text-sm">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500 mt-auto">
        <div class="max-w-7xl mx-auto px-4">
            Centrálny dokumentačný systém pre KAPZ a APZ &copy; {{ date('Y') }} | Referenčný model Excel 2026
        </div>
    </footer>

</body>
</html>
