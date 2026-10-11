@php $editing = isset($department) && $department !== null; @endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="name">Назва департаменту <span class="text-danger">*</span></label>
        <input id="name" name="name" type="text" maxlength="100" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $editing ? $department->name : '') }}"
               placeholder="Відділ">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="code">Код департаменту <span class="text-danger">*</span></label>
        <input id="code" name="code" type="text" maxlength="20" required
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $editing ? $department->code : '') }}"
               placeholder="Наприклад: IT">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Назва й код мають бути унікальними.</div>
    </div>
</div>
