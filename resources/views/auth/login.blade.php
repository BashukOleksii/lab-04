@extends('layout.app')

@section('title', 'Вхід')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h2 class="mb-4 text-center">
                        Вхід до RepairDesk
                    </h2>

                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Електронна пошта
                            </label>

                            <input type="email" id="email" name="email" class="form-control"
                                value="{{ old('email') }}" autocomplete="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Пароль
                            </label>

                            <input type="password" id="password" name="password" class="form-control"
                                autocomplete="current-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Увійти
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
