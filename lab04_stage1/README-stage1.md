# Лабораторна №4 — етап 1: заявки на ремонт

Це набір **підготовлених файлів для ручного перенесення** до локального проєкту `lab-04`. Нічого не пушилося до GitHub.

## Оновити/створити

- `app/Http/Controllers/RepairRequestController.php` — 7 resource methods і download.
- `app/Services/RepairDefectFileService.php` — зберігання, перевірка, URL, завантаження, видалення.
- `app/Http/Requests/ReadRepairRequestRequest.php` — виправлено 403, додано sort.
- `app/Http/Requests/StoreRepairRequestRequest.php` — валідація заявки, JPG/PDF ≤2560 КБ, optional initial comment.
- `app/Http/Requests/UpdateRepairRequestRequest.php` — виправлено required_if, дата, file replacement.
- `app/Models/RepairRequest.php` — fillable defect_path і очищено імпорти, наявний scope збережено.
- `routes/web.php` — resource routes і download.

## Перевірити

```sh
./vendor/bin/sail artisan route:list --path=repair-requests
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail pint --test
```

**Увага:** у репозиторії наразі немає `resources/views/repair-requests/*.blade.php`.
Контролер скомпілюється, але GET index/create/show/edit до створення цих Blade-шаблонів повертатимуть помилку `View not found`. Це наступний етап після контролерів.

## Важливі умови інтеграції

- Форма має використовувати `enctype="multipart/form-data"` для завантаження файла під назвою поля `defect_file`.
- Поле `urgent` — `required|boolean`; для unchecked checkbox надсилайте `<input type="hidden" name="urgent" value="0">` і checkbox `value="1"`.
- Для `status=closed` обов'язкові `cost` і `completed_at`.
- Пагінація коментарів використовує параметр `comments_page`.
- Сервіс використовує **public disk**; файли будуть доступні за публічним URL. Для конфіденційних дефектів слід перейти на private disk + авторизований streaming.

## Важлива відома межа

SQL-транзакція не охоплює файлову систему. Для базової лабораторної реалізовано компенсацію при неуспішному записі БД. У рідкісному випадку помилки **видалення** файла вже після commit потрібне повторне очищення (наприклад, queue job), інакше можливий orphan file. Для суворої гарантії в production додайте механізм retry/pending-deletion.
