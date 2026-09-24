@extends('layouts.app')

@section('title', 'Prihlásenie - KAPZ/APZ Systém')

@section('content')
<div class="max-w-md mx-auto my-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-8 text-center text-white">
            <div class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-2xl mx-auto flex items-center justify-center font-black text-2xl shadow-lg mb-3">
                K
            </div>
            <h1 class="text-xl font-bold">KAPZ / APZ Informačný Systém</h1>
            <p class="text-xs text-slate-300 mt-1">Prihlásenie do dokumentačného portálu</p>
        </div>

        <!-- Card Body -->
        <div class="p-8">
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">E-mailová adresa</label>
                    <input type="email" id="email" name="email" value="{{ old('email', 'novak@kapz.sk') }}" required autofocus
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 text-sm font-medium transition">
                    @error('email')
                        <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Heslo</label>
                    <input type="password" id="password" name="password" value="password" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 text-sm font-medium transition">
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/25 transition transform active:scale-98">
                    Prihlásiť sa
                </button>
            </form>

            <!-- Quick Login Helper Buttons for Easy Demo Testing -->
            <div class="mt-8 pt-6 border-t border-slate-100">
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider text-center mb-3">Rýchle Demo Prihlásenie</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="document.getElementById('email').value='novak@kapz.sk'; document.getElementById('password').value='password';"
                        class="py-2 px-3 bg-blue-50 hover:bg-blue-100 text-blue-800 rounded-lg text-xs font-semibold border border-blue-200 transition text-center">
                        👤 KAPZ (novak@kapz.sk)
                    </button>
                    <button type="button" onclick="document.getElementById('email').value='admin@kapz.sk'; document.getElementById('password').value='password';"
                        class="py-2 px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded-lg text-xs font-semibold border border-indigo-200 transition text-center">
                        ⚡ Admin (admin@kapz.sk)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
