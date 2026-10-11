@extends('layout.app')

@section('title', 'Департаменти')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="page-eyebrow mb-2">Довідник</div>
            <h1 class="page-title display-6 mb-2">Департаменти</h1>
            <p class="text-secondary mb-0">Перегляд та керування підрозділами.</p>
        </div>
        <a href="{{ route('departments.create') }}" class="btn btn-brand px-4">+ Додати департамент</a>
    </div>

    <div class="surface-card p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('departments.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-8">
                <label for="search" class="form-label">Пошук за назвою</label>
                <input type="search" id="search" name="search" maxlength="100" class="form-control"
                       placeholder="Відділ" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-brand flex-grow-1">Знайти</button>
                <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Скинути</a>
            </div>
        </form>
    </div>

    <section class="surface-card overflow-hidden">
        <div class="d-flex justify-content-between flex-wrap gap-2 px-4 pt-4 pb-2">
            <h2 class="h5 fw-bold mb-0">Список департаментів</h2>
            <span class="text-secondary small">Знайдено: {{ $departments->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Назва</th>
                        <th>Код</th>
                        <th>Заявок</th>
                        <th class="text-end pe-4">Дії</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($departments as $department)
                    <tr>
                        <td class="ps-4 fw-semibold">
                            <a class="text-decoration-none" href="{{ route('departments.show', $department) }}">{{ $department->name }}</a>
                        </td>
                        <td><span class="badge text-bg-light border">{{ $department->code }}</span></td>
                        <td>{{ $department->repair_requests_count }}</td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('departments.show', $department) }}">Деталі</a>
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('departments.edit', $department) }}">Редагувати</a>
                                @if($department->repair_requests_count === 0)
                                    <form method="POST" action="{{ route('departments.destroy', $department) }}"
                                          onsubmit="return confirm('Видалити департамент?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Видалити</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-5">Департаментів не знайдено.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $departments->links('pagination::bootstrap-5') }}</div>
@endsection
