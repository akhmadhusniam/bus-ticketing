@extends('layouts.app')

@section('title', 'Hasil Pencarian')

@section('content')
<section class="container page">
    <h1>{{ $origin->name }} &rarr; {{ $destination->name }}</h1>
    <p class="muted">
        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} &middot; {{ $passengers }} penumpang
    </p>

    <div class="card">
        Daftar bus untuk rute ini akan tampil di sini (langkah berikutnya).
    </div>

    <p><a href="{{ route('home') }}#cari">&larr; Ubah pencarian</a></p>
</section>
@endsection
