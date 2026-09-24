@extends('layouts.app')

@section('title', 'Chýba profil KAPZ')

@section('content')
<div class="bg-white p-8 rounded-2xl shadow-sm border border-amber-200 max-w-xl mx-auto text-center space-y-3">
    <div class="text-3xl">⚠️</div>
    <h1 class="text-lg font-bold text-slate-900">K vášmu účtu nie je priradený profil KAPZ</h1>
    <p class="text-sm text-slate-600">
        Bez profilu nie je možné zobraziť dochádzku, knihy ani plán pracovných ciest.
        Požiadajte administrátora, aby váš účet prepojil s profilom KAPZ.
    </p>
</div>
@endsection
