@extends('layout.app')

@section('title', 'Редагування департаменту')

@section('content')
    <div class="mb-4">
        <a href="{{ route('departments.show', $department) }}" class="small text-decoration-none"><- До департаменту</a>
        <div class="page-eyebrow mt-3 mb-2">Довідник</div>
        <h1 class="page-title h2">Редагування департаменту</h1>
    </div>
    <div class="surface-card p-4 p-lg-5 mx-auto" style="max-width: 740px;">
        <form action="{{ route('departments.update', $department) }}" method="POST">
            @csrf
            @method('PUT')
            @include('departments._form', ['department' => $department])
            <div class="d-flex flex-wrap gap-2 border-top mt-4 pt-4">
                <button class="btn btn-brand" type="submit">Зберегти зміни</button>
                <a class="btn btn-outline-secondary" href="{{ route('departments.show', $department) }}">Скасувати</a>
            </div>
        </form>
    </div>
@endsection
