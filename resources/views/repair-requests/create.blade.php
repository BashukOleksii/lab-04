@extends('layout.app')

@section('title', 'Нова заявка')

@section('content')
    <div class="mb-4">
        <a class="small text-decoration-none" href="{{ route('repair-requests.index') }}">← Назад до заявок</a>
        <div class="page-eyebrow mt-3 mb-2">Нове звернення</div>
        <h1 class="page-title h2 mb-1">Створення заявки</h1>
        <p class="text-secondary mb-0">Заповніть інформацію про несправність.</p>
    </div>

    <div class="surface-card p-4 p-lg-5">
        <form action="{{ route('repair-requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('repair-requests._form', ['repairRequest' => null, 'departments' => $departments])
            <div class="border-top pt-4 mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-brand px-4">Створити заявку</button>
                <a href="{{ route('repair-requests.index') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>
    </div>
@endsection
