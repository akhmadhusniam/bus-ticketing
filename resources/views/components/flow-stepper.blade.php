@props([
    'current' => 1,
    'paymentDone' => true,
])

@php
    $current = max(1, min(5, (int) $current));
    $steps = [
        'Cari',
        'Detail trip',
        'Pilih kursi',
        'Pembayaran',
        'E-tiket',
    ];
@endphp

<ol class="flow-steps" aria-label="Langkah {{ $current }} dari 5: pemesanan tiket">
    @foreach ($steps as $index => $label)
        @php
            $number = $index + 1;
            $isActive = $number === $current;
            $isDone = $number < $current;

            if ($number === 4 && ! $paymentDone) {
                $isDone = false;
            }
        @endphp
        <li class="flow-step {{ $isActive ? 'is-active' : ($isDone ? 'is-done' : '') }}"
            @if ($isActive) aria-current="step" @endif>
            <span class="flow-step-marker" aria-hidden="true">{!! $isDone ? '&#10003;' : (string) $number !!}</span>
            <span class="flow-step-label">{{ $label }}</span>
            <span class="visually-hidden">
                {{ $isActive ? 'Sedang berjalan' : ($isDone ? 'Selesai' : 'Belum dimulai') }}
            </span>
        </li>
    @endforeach
</ol>
