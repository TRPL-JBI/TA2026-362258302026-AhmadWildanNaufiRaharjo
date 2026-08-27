@extends('layouts.app')

@section('title', 'Pemantauan IPAL - Safety Patrol K3LH')
@section('page_title', 'Pemantauan IPAL')

@php
    use Illuminate\Support\Js;

    $triwulanKeys = array_keys($ipalPageConfig['triwulanToBulan'] ?? []);
@endphp

@section('content')
    <div class="space-y-4" x-data="pemantauanIpal({{ Js::from($ipalPageConfig) }})">
        <x-pemantauan.ipal.list />
        <x-pemantauan.ipal.form :triwulan-keys="$triwulanKeys" />
        <x-pemantauan.ipal.modal-sukses />
    </div>
@endsection
