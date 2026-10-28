<section class="surface-card p-4 mt-4">
    <h2 class="h5 fw-bold mb-3">
        Приватний документ перевірки
    </h2>

    @if ($repairRequest->review_file_path)
        @can('downloadPrivate', $repairRequest)
            <a href="{{ route('repair-requests.review-file.download', $repairRequest) }}"
                class="btn btn-outline-primary mb-3">
                Завантажити документ
            </a>
        @endcan
    @else
        <p class="text-secondary">
            Документ ще не додано.
        </p>
    @endif

    @can('update', $repairRequest)
        <form method="POST" action="{{ route('repair-requests.review-file.store', $repairRequest) }}"
            enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="review_file" class="form-label">
                    PDF-документ
                </label>

                <input type="file" id="review_file" name="review_file" accept=".pdf,application/pdf"
                    class="form-control @error('review_file') is-invalid @enderror" required>

                @error('review_file')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Зберегти документ
            </button>
        </form>
    @endcan
</section>
