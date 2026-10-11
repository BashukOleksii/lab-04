@extends('layout.app')

@section('title', 'Новий департамент')

@section('content')
    <div class="mb-4">
        <a href="{{ route('departments.index') }}" class="small text-decoration-none"><- Назад до департаментів</a>
        <div class="page-eyebrow mt-3 mb-2">Довідник</div>
        <h1 class="page-title h2">Новий департамент</h1>
    </div>
    <div class="surface-card p-4 p-lg-5 mx-auto" style="max-width: 740px;">
        <form action="{{ route('departments.store') }}" method="POST">
            @csrf
            @include('departments._form', ['department' => null])
            <div class="d-flex flex-wrap gap-2 border-top mt-4 pt-4">
                <button class="btn btn-brand" type="submit">Створити департамент</button>
                <a class="btn btn-outline-secondary" href="{{ route('departments.index') }}">Скасувати</a>
            </div>
        </form>
    </div>
@endsection
