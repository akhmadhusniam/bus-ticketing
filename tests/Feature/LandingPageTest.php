<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_beranda_menampilkan_blok_utama(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Switcher moda transportasi', false)
            ->assertSee('Promo dengan kode voucher')
            ->assertSee('WEEKEND20')
            ->assertSee('rata-rata ulasan penumpang')
            ->assertSee('Operator bus & shuttle pilihan', false)
            ->assertSee('Kata mereka yang sudah berangkat')
            ->assertSee('Pertanyaan umum')
            ->assertSee('Pilih kota asal')
            ->assertSee('Pilih kota tujuan')
            ->assertSee('Tanggal pergi')
            ->assertSee('Tanggal pulang')
            ->assertSee('Cari Bus');
    }

    public function test_mode_shuttle_mengubah_label_pencarian(): void
    {
        $this->get('/?mode=shuttle')
            ->assertOk()
            ->assertSee('Cari Shuttle')
            ->assertSee('value="shuttle"', false);
    }
}
