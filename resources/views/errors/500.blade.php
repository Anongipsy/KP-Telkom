@extends('errors.layout')

@section('title', '500 Kesalahan Server')
@section('code', '500')
@section('heading', 'Kesalahan Server Internal')
@section('icon-bg', 'bg-red-500/10 border border-red-500/20 text-red-400')

@section('icon')
<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
</svg>
@endsection

@section('message', 'Terjadi kendala pada server saat memproses operasi ini. Detail error telah dicatat ke audit log dan sistem log untuk penanganan teknis.')
