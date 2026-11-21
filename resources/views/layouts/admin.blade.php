<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title','Dashboard') · Admin Ruang Belanja</title>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}">
</head>
<body>

<div class="admin-wrap">
    <aside class="admin-side">
        <a class="brand" href="{{ route('admin.dashboard') }}">ruangbelanja.</a>
        <div class="eyebrow">Ruang Pengelola</div>

        @php
            $adminLinks = [
                ['admin.dashboard', 'Ringkasan'],
                ['admin.categories.index', 'Kategori'],
                ['admin.products.index', 'Produk'],
                ['admin.stock.index', 'Stok & Barang Masuk'],
                ['admin.shipping.index', 'Ekspedisi'],
                ['admin.orders.index', 'Transaksi'],
            ];
        @endphp

        @foreach($adminLinks as $link)
            @if(Route::has($link[0]))
                <a href="{{ route($link[0]) }}"
                    @if(request()->routeIs($link[0] === 'admin.dashboard' ? $link[0] : str_replace('.index', '.*', $link[0]))) aria-current="page" @endif>
                    {{ $link[1] }}
                </a>
            @endif
        @endforeach

        <a href="{{ route('shop.index') }}">↗ Lihat Toko</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-light">Keluar</button>
        </form>
    </aside>

    <main class="admin-main">
        <div class="admin-context">
            <span>Administrasi / @yield('title','Dashboard')</span>
            <span>{{ auth()->user()->name }} · Admin</span>
        </div>

        @include('layouts.flash')
        @yield('content')
    </main>
</div>

<script src="{{ asset('js/store.js') }}" defer></script>
</body>
</html>