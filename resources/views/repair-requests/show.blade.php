@extends('layout.app')

@section('title', 'Заявка #' . $repairRequest->id)

@section('content')
    @php
        $statusName = match($repairRequest->status) {
            'open' => 'Відкрита',
            'in_progress' => 'У роботі',
            'closed' => 'Закрита',
            default => $repairRequest->status,
        };
        $statusClass = match($repairRequest->status) {
            'open' => 'text-bg-info',
            'in_progress' => 'text-bg-warning',
            'closed' => 'text-bg-success',
            default => 'text-bg-secondary',
        };
    @endphp

    <div class="mb-4">
        <a href="{{ route('repair-requests.index') }}" class="small text-decoration-none">← Назад до заявок</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3">
            <div>
                <div class="page-eyebrow mb-2">Заявка #{{ $repairRequest->id }}</div>
                <h1 class="page-title h2 mb-2">{{ $repairRequest->subject }}</h1>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge {{ $statusClass }}">{{ $statusName }}</span>
                    <span class="badge text-bg-light border">Пріоритет {{ $repairRequest->priority }}</span>
                    @if($repairRequest->urgent)
                        <span class="badge text-bg-danger">Термінова</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('repair-requests.edit', $repairRequest) }}" class="btn btn-brand">Редагувати</a>
                <form method="POST" action="{{ route('repair-requests.destroy', $repairRequest) }}"
                      onsubmit="return confirm('Видалити заявку разом з усіма коментарями та файлом?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Видалити</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="surface-card p-4 p-lg-5 h-100">
                <h2 class="h5 fw-bold mb-4">Інформація про заявку</h2>
                <div class="row g-4">
                    <div class="col-sm-6"><div class="muted-label">Департамент</div><div class="value-text">{{ $repairRequest->department->name }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Пріоритет</div><div class="value-text">{{ $repairRequest->priority }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Статус</div><div class="value-text">{{ $statusName }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Терміновість</div><div class="value-text">{{ $repairRequest->urgent ? 'Термінова' : 'Звичайна' }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Дата повідомлення</div><div class="value-text">{{ $repairRequest->reported_at?->format('d.m.Y H:i') }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Дата завершення</div><div class="value-text">{{ $repairRequest->completed_at?->format('d.m.Y H:i') ?? 'Не завершено' }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Вартість</div><div class="value-text">{{ $repairRequest->cost !== null ? number_format((float) $repairRequest->cost, 2, ',', ' ') . ' грн' : 'Не вказана' }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Створено запис</div><div class="value-text">{{ $repairRequest->created_at?->format('d.m.Y H:i') }}</div></div>
                    <div class="col-sm-6"><div class="muted-label">Востаннє змінено</div><div class="value-text">{{ $repairRequest->updated_at?->format('d.m.Y H:i') }}</div></div>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="surface-card p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Фото або документ дефекту</h2>
                @include('repair-requests._file')
            </section>
        </div>
    </div>

    @include('repair-requests._comments')
@endsection
