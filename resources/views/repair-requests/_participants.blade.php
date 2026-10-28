<section class="surface-card p-4 mt-4">

    <h2 class="h5 fw-bold mb-3">
        Учасники заявки
    </h2>

    <p class="text-secondary">
        Власник: {{ $repairRequest->owner->name }}
    </p>

    @forelse ($repairRequest->participatedUsers as $participant)
        <div class="d-flex justify-content-between align-items-center border-top py-3">
            <div>
                <strong>{{ $participant->name }}</strong>

                <div class="small text-secondary">
                    {{ $participant->email }}
                    — {{ $participant->pivot->role }}
                </div>
            </div>

            @can('manageParticipants', $repairRequest)
                <form method="POST"
                    action="{{ route('repair-requests.participants.destroy', [$repairRequest, $participant]) }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        Видалити
                    </button>
                </form>
            @endcan
        </div>
    @empty
        <p class="text-secondary">
            Учасників ще не призначено.
        </p>
    @endforelse

    @can('manageParticipants', $repairRequest)
        <form method="POST" action="{{ route('repair-requests.participants.store', $repairRequest) }}"
            class="border-top pt-3 mt-3">
            @csrf

            <div class="row g-3">

                <div class="col-md-6">
                    <label for="user_id" class="form-label">
                        Користувач
                    </label>

                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Оберіть користувача</option>

                        @foreach ($usersForParticipants as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="role" class="form-label">
                        Роль
                    </label>

                    <select name="role" id="role" class="form-select" required>
                        <option value="viewer" @selected(old('role') === 'viewer')>Viewer</option>
                        <option value="editor" @selected(old('role') === 'editor')>Editor</option>
                        <option value="reviewer" @selected(old('role') === 'reviewer')>Reviewer</option>
                    </select>
                    @error('role')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <button type="submit" class="btn btn-primary mt-3">
                Зберегти роль
            </button>
        </form>
    @endcan

</section>
