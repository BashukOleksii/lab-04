@extends('layout.app')

@section('title', 'Реєстрація')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h2 class="mb-4 text-center">
                        Реєстрація в RepairDesk
                    </h2>

                    <form method="POST" action="{{ route('register.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Ім'я користувача
                            </label>

                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror" maxlength="255" autocomplete="name"
                                required>

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Електронна пошта
                            </label>

                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror" maxlength="255"
                                autocomplete="email" required>

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Пароль
                            </label>

                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" minlength="8"
                                autocomplete="new-password" required>

                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Підтвердження пароля
                            </label>

                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" minlength="8" autocomplete="new-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Зареєструватися
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        Уже маєте акаунт?
                        <a href="{{ route('login') }}">
                            Увійти
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
@endsection
