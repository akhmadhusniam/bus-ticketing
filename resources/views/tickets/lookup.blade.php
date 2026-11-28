@extends('layouts.app')

@section('title', 'Cek Tiket Saya')
@section('meta_description', 'Cek status dan detail e-tiket bus Anda hanya dengan kode booking dan email pemesan.')

@section('content')
<section class="container page">
    <h1>Cek Tiket Saya</h1>
    <p class="lead">Masukkan kode booking dan email yang dipakai saat memesan.</p>

    <div class="card lookup-card">
        @if ($errors->any())
            <div class="alert" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ticket.find') }}">
            @csrf

            <label class="field">
                Kode booking
                <input type="text" name="code" value="{{ old('code') }}" maxlength="12" required
                       placeholder="Contoh: DEMO2026" autocomplete="off">
            </label>

            <label class="field">
                Email pemesan
                <input type="email" name="email" value="{{ old('email', 'pemesan@example.com') }}" required
                       placeholder="nama@example.com" autocomplete="email">
            </label>

            <button class="btn" type="submit">Cek tiket</button>
        </form>

        <p class="muted small">Data demo: kode <code>DEMO2026</code> dengan email <code>pemesan@example.com</code>.</p>
    </div>
</section>
@endsection
