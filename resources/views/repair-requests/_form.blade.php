@php
    $editing = isset($repairRequest) && $repairRequest !== null;
    $reportedValue = $editing ? $repairRequest->reported_at?->format('Y-m-d\TH:i') : '';
    $completedValue = $editing ? $repairRequest->completed_at?->format('Y-m-d\TH:i') : '';
@endphp

<div class="row g-3">
    <div class="col-12">
        <label for="subject" class="form-label">Тема заявки <span class="text-danger">*</span></label>
        <input type="text" id="subject" name="subject" maxlength="200" required
               class="form-control @error('subject') is-invalid @enderror"
               value="{{ old('subject', $editing ? $repairRequest->subject : '') }}">
        @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="department_id" class="form-label">Департамент <span class="text-danger">*</span></label>
        <select id="department_id" name="department_id" required class="form-select @error('department_id') is-invalid @enderror">
            <option value="">Оберіть департамент</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}"
                    @selected(old('department_id', $editing ? $repairRequest->department_id : '') == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label for="priority" class="form-label">Пріоритет <span class="text-danger">*</span></label>
        <select id="priority" name="priority" required class="form-select @error('priority') is-invalid @enderror">
            @for($priority = 1; $priority <= 3; $priority++)
                <option value="{{ $priority }}" @selected(old('priority', $editing ? $repairRequest->priority : 2) == $priority)>
                    {{ $priority }}
                </option>
            @endfor
        </select>
        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label for="status" class="form-label">Статус <span class="text-danger">*</span></label>
        <select id="status" name="status" required class="form-select @error('status') is-invalid @enderror">
            <option value="open" @selected(old('status', $editing ? $repairRequest->status : 'open') === 'open')>Відкрита</option>
            <option value="in_progress" @selected(old('status', $editing ? $repairRequest->status : '') === 'in_progress')>У роботі</option>
            <option value="closed" @selected(old('status', $editing ? $repairRequest->status : '') === 'closed')>Закрита</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="reported_at" class="form-label">Дата повідомлення <span class="text-danger">*</span></label>
        <input type="datetime-local" id="reported_at" name="reported_at" required
               class="form-control @error('reported_at') is-invalid @enderror"
               value="{{ old('reported_at', $reportedValue) }}">
        @error('reported_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="completed_at" class="form-label">Дата завершення</label>
        <input type="datetime-local" id="completed_at" name="completed_at"
               class="form-control @error('completed_at') is-invalid @enderror"
               value="{{ old('completed_at', $completedValue) }}">
        @error('completed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Обов'язкова для закритої заявки. Не раніше дати повідомлення.</div>
    </div>

    <div class="col-md-6">
        <label for="cost" class="form-label">Вартість ремонту, грн</label>
        <input type="number" id="cost" name="cost" step="0.01" min="0" max="99999999.99"
               class="form-control @error('cost') is-invalid @enderror"
               value="{{ old('cost', $editing ? $repairRequest->cost : '') }}">
        @error('cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Обов'язкова для закритої заявки.</div>
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="urgent" value="0">
            <input type="checkbox" class="form-check-input" id="urgent" name="urgent" value="1"
                   @checked((string) old('urgent', $editing && $repairRequest->urgent ? '1' : '0') === '1')>
            <label class="form-check-label fw-semibold" for="urgent">Термінова заявка</label>
            @error('urgent') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-12">
        <label for="defect_file" class="form-label">Файл дефекту (JPG або PDF)</label>
        <input type="file" class="form-control @error('defect_file') is-invalid @enderror"
               id="defect_file" name="defect_file" accept=".jpg,.jpeg,.pdf,image/jpeg,application/pdf">
        @error('defect_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Необов'язково, до 2560 КБ. При редагуванні новий файл замінить попередній.</div>
    </div>

    @unless($editing)
        <div class="col-12">
            <label for="initial_comment" class="form-label">Початковий коментар</label>
            <textarea id="initial_comment" name="initial_comment" rows="3" maxlength="2000"
                      class="form-control @error('initial_comment') is-invalid @enderror"
                      placeholder="Опишіть несправність або перші дії...">{{ old('initial_comment') }}</textarea>
            @error('initial_comment') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endunless
</div>
