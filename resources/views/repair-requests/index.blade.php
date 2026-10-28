@extends('layout.app')

@section('title', 'Заявки на ремонт')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="page-eyebrow mb-2">Облік ремонтів</div>
            <h1 class="page-title display-6 mb-2">Заявки на ремонт</h1>
            <p class="text-secondary mb-0">Пошук, фільтрація й облік усіх звернень.</p>
        </div>
        @can('create', \App\Models\RepairRequest::class)
            <a href="{{ route('repair-requests.create') }}" class="btn btn-brand px-4">
                + Створити заявку
            </a>
        @endcan
    </div>

    <div class="surface-card p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('repair-requests.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="search">Пошук</label>
                    <input class="form-control" type="search" id="search" name="search" maxlength="2000"
                        value="{{ $filters['search'] ?? '' }}" placeholder="Тема заявки або текст коментаря">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="department_id">Департамент</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Усі</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="status">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Усі</option>
                        <option value="open" @selected(($filters['status'] ?? '') === 'open')>Відкрита</option>
                        <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>У роботі</option>
                        <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Закрита</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="sort">Сортувати за</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="date" @selected(($filters['sort'] ?? 'date') === 'date')>Датою</option>
                        <option value="priority" @selected(($filters['sort'] ?? '') === 'priority')>Пріоритетом</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="direction">Порядок</label>
                    <select class="form-select" id="direction" name="direction">
                        <option value="desc" @selected(($filters['direction'] ?? 'desc') === 'desc')>За спаданням</option>
                        <option value="asc" @selected(($filters['direction'] ?? '') === 'asc')>За зростанням</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-brand px-4">Застосувати</button>
                <a class="btn btn-outline-secondary" href="{{ route('repair-requests.index') }}">Скинути</a>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <span class="text-secondary">Знайдено заявок: <strong class="text-dark">{{ $repairs->total() }}</strong></span>
        @if ($repairs->total() > 0)
            <span class="text-secondary">Показано {{ $repairs->firstItem() }}–{{ $repairs->lastItem() }}</span>
        @endif
    </div>

    @if ($repairs->isEmpty())
        <div class="surface-card p-5 text-center">
            <h2 class="h5">Заявок не знайдено</h2>
            <p class="text-secondary mb-3">Спробуйте змінити фільтри або створіть першу заявку.</p>
            <a href="{{ route('repair-requests.index') }}" class="btn btn-outline-secondary">Очистити фільтри</a>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 g-lg-4">
            @foreach ($repairs as $repair)
                @php
                    $statusName = match ($repair->status) {
                        'open' => 'Відкрита',
                        'in_progress' => 'У роботі',
                        'closed' => 'Закрита',
                        default => $repair->status,
                    };
                    $statusClass = match ($repair->status) {
                        'open' => 'text-bg-info',
                        'in_progress' => 'text-bg-warning',
                        'closed' => 'text-bg-success',
                        default => 'text-bg-secondary',
                    };
                @endphp
                <div class="col">
                    <article class="card border-0 surface-card request-card h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <span class="badge rounded-pill {{ $statusClass }}">{{ $statusName }}</span>
                                <span class="small text-secondary">#{{ $repair->id }}</span>
                            </div>
                            <h2 class="card-title h5 mb-2">
                                <a class="stretched-link"
                                    href="{{ route('repair-requests.show', $repair) }}">{{ $repair->subject }}</a>
                            </h2>
                            <p class="text-secondary small mb-3">{{ $repair->department->name }}</p>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge text-bg-light border">Пріоритет {{ $repair->priority }}</span>
                                @if ($repair->urgent)
                                    <span class="badge text-bg-danger">Термінова</span>
                                @endif
                            </div>
                            <div
                                class="small text-secondary mt-auto d-flex justify-content-between gap-2 flex-wrap pt-3 border-top">
                                <span>{{ $repair->reported_at?->format('d.m.Y H:i') }}</span>
                                <span>Коментарів: {{ $repair->repair_comments_count }}</span>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $repairs->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection
