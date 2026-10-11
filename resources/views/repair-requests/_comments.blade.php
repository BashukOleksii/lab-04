<section class="surface-card p-4 p-lg-5 mt-4" id="comments">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="h4 fw-bold mb-0">Коментарі</h2>
        <span class="badge text-bg-light border">Усього: {{ $comments->total() }}</span>
    </div>

    <form method="POST" action="{{ route('repair-requests.comments.store', $repairRequest) }}" class="mb-4">
        @csrf
        <label for="new-comment-body" class="form-label">Додати коментар</label>
        <textarea id="new-comment-body" name="body" rows="3" maxlength="2000" required
                  class="form-control @error('body') is-invalid @enderror"
                  placeholder="Наприклад: Проведено первинну діагностику...">{{ old('editing_comment_id') ? '' : old('body') }}</textarea>
        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <button class="btn btn-brand mt-3" type="submit">Опублікувати коментар</button>
    </form>

    @forelse($comments as $comment)
        <article class="border-top py-3" id="comment-{{ $comment->id }}">
            <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                <span class="fw-semibold">Коментар #{{ $comment->id }}</span>
                <time class="small text-secondary">{{ $comment->created_at?->format('d.m.Y H:i') }}</time>
            </div>
            <p class="comment-body mb-3">{{ $comment->body }}</p>
            <div class="d-flex align-items-start flex-wrap gap-3">
                <details class="flex-grow-1" @if((string) old('editing_comment_id') === (string) $comment->id) open @endif>
                    <summary class="small text-decoration-underline" style="cursor:pointer">Редагувати</summary>
                    <form method="POST" action="{{ route('repair-requests.comments.update', [$repairRequest, $comment]) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="editing_comment_id" value="{{ $comment->id }}">
                        <label class="visually-hidden" for="comment-edit-{{ $comment->id }}">Редагувати коментар</label>
                        <textarea class="form-control" rows="3" maxlength="2000" required
                                  id="comment-edit-{{ $comment->id }}" name="body">{{ (string) old('editing_comment_id') === (string) $comment->id ? old('body') : $comment->body }}</textarea>
                        @if((string) old('editing_comment_id') === (string) $comment->id)
                            @error('body') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @endif
                        <button class="btn btn-outline-primary btn-sm mt-2" type="submit">Зберегти зміни</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('repair-requests.comments.destroy', [$repairRequest, $comment]) }}"
                      onsubmit="return confirm('Видалити цей коментар?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Видалити</button>
                </form>
            </div>
        </article>
    @empty
        <p class="text-secondary border-top pt-3">Коментарів ще немає.</p>
    @endforelse

    @if($comments->hasPages())
        <div class="pt-3">{{ $comments->fragment('comments')->links('pagination::bootstrap-5') }}</div>
    @endif
</section>
