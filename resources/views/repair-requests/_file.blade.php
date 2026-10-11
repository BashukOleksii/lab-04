@if($fileUrl)
    @if($fileIsPdf)
        <div class="ratio ratio-16x9 border rounded-3 overflow-hidden">
            <iframe src="{{ $fileUrl }}" title="PDF-документ дефекту"></iframe>
        </div>
    @else
        <img class="file-preview d-block mx-auto" src="{{ $fileUrl }}" alt="Зображення дефекту" loading="lazy">
    @endif
    <div class="mt-3">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('repair-requests.download', $repairRequest) }}">
            Завантажити файл
        </a>
    </div>
@elseif($repairRequest->defect_path)
    <div class="alert alert-warning mb-0">Файл вказаний у БД, але фізично відсутній у сховищі.</div>
@else
    <p class="text-secondary mb-0">До заявки не прикріплено файл.</p>
@endif
