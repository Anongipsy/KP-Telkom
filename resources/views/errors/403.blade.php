@extends('errors.layout')

@section('title', '403 Akses Ditolak')
@section('code', '403')
@section('heading', 'Akses Ditolak')
@section('icon-bg', 'bg-amber-500/10 border border-amber-500/20 text-amber-400')

@section('icon')
<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
</svg>
@endsection

@section('message', 'Anda tidak memiliki izin (permission/role) yang sesuai untuk mengakses halaman atau fungsi ini.')
