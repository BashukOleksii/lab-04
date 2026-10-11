<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Заявки на ремонт') — RepairDesk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>
<body class="d-flex flex-column">
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container">
        <a class="navbar-brand" href="{{ route('repair-requests.index') }}">RepairDesk<span class="text-info">.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavigation"
                aria-controls="appNavigation" aria-expanded="false" aria-label="Відкрити меню">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="appNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('repair-requests.*') ? 'active' : '' }}"
                       href="{{ route('repair-requests.index') }}">Заявки на ремонт</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
                       href="{{ route('departments.index') }}">Департаменти</a>
                </li>
                <li class="nav-item mt-2 mt-lg-0">
                    <a class="btn btn-light btn-sm fw-semibold px-3" href="{{ route('repair-requests.create') }}">+ Нова заявка</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5 flex-grow-1">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Перевірте введені дані:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="container pb-4 pt-2 small d-flex justify-content-between flex-wrap gap-2">
    <span>RepairDesk · Лабораторна робота №4</span>
    <span>Laravel · Eloquent · Bootstrap</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
