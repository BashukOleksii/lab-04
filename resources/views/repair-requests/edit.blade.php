@extends('layout.app')

@section('title', 'Редагування заявки #' . $repairRequest->id)

@section('content')
    <div class="mb-4">
        <a href="{{ route('repair-requests.show', $repairRequest) }}" class="small text-decoration-none">← До деталей заявки</a>
        <div class="page-eyebrow mt-3 mb-2">Заявка #{{ $repairRequest->id }}</div>
        <h1 class="page-title h2 mb-1">Редагування заявки</h1>
        <p class="text-secondary mb-0">{{ $repairRequest->subject }}</p>
    </div>

    <section class="surface-card p-4 p-lg-5">
        <form action="{{ route('repair-requests.update', $repairRequest) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('repair-requests._form', ['repairRequest' => $repairRequest, 'departments' => $departments])
            <div class="border-top pt-4 mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-brand px-4">Зберегти зміни</button>
                <a class="btn btn-outline-secondary" href="{{ route('repair-requests.show', $repairRequest) }}">Скасувати</a>
            </div>
        </form>
    </section>

    <section class="surface-card p-4 mt-4">
        <h2 class="h5 fw-bold mb-3">Поточний файл дефекту</h2>
        @include('repair-requests._file')
    </section>

    @include('repair-requests._comments')
@endsection
