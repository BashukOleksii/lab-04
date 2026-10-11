@extends('layout.app')

@section('title', $department->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('departments.index') }}" class="small text-decoration-none"><- Назад до департаментів</a>
        <div class="page-eyebrow mt-3 mb-2">Департамент {{ $department->id }}</div>
        <h1 class="page-title h2">{{ $department->name }}</h1>
    </div>

    <div class="surface-card p-4 p-lg-5">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="muted-label">Код</div>
                <div class="value-text">{{ $department->code }}</div>
            </div>
            <div class="col-md-4">
                <div class="muted-label">Кількість заявок</div>
                <div class="value-text">{{ $department->repair_requests_count }}</div>
            </div>
            <div class="col-md-4">
                <div class="muted-label">Дата створення</div>
                <div class="value-text">{{ $department->created_at?->format('d.m.Y H:i') }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 border-top pt-4">
            <a href="{{ route('departments.edit', $department) }}" class="btn btn-brand">Редагувати</a>
            <a href="{{ route('repair-requests.index', ['department_id' => $department->id]) }}"
               class="btn btn-outline-secondary">Переглянути заявки</a>
            @if($department->repair_requests_count === 0)
                <form method="POST" action="{{ route('departments.destroy', $department) }}"
                      onsubmit="return confirm('Видалити департамент?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Видалити</button>
                </form>
            @else
                <span class="text-secondary small align-self-center">Видалення недоступне: є прив'язані заявки.</span>
            @endif
        </div>
    </div>
@endsection
