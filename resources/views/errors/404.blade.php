@extends('errors.layout')

@section('title', '404 Halaman Tidak Ditemukan')
@section('code', '404')
@section('heading', 'Halaman Tidak Ditemukan')
@section('icon-bg', 'bg-slate-800 border border-slate-700 text-slate-400')

@section('icon')
<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
</svg>
@endsection

@section('message', 'Halaman atau data yang Anda cari tidak ditemukan. Periksa kembali tautan atau kembali ke dashboard.')
