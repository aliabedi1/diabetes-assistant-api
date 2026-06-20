# CLAUDE.md — Agent Behavior for Diabetes Assistant API

This file defines how AI agents must behave when working in this Laravel API project.
**Read MEMORY.md first before making any changes.**

---

## 1. Mandatory Rules

- **Always read `MEMORY.md` before making any change.** It contains architecture decisions, conventions, and known pitfalls that directly affect how code must be written.
- **Check existing patterns before adding new code.** Look at an existing controller, request, and resource in the same domain before creating new ones.
- **Never introduce new architectural layers** (e.g., repositories, service classes, events) without explicit user request and a corresponding update to `MEMORY.md`.
- **Use `reliese/laravel`** to regenerate model docblocks after running new migrations (`php artisan code:models`).

---

## 2. Development Workflow

For every task, follow this sequence:

1. **Analyze** — read `MEMORY.md`, understand existing patterns in the affected domain
2. **Plan** — identify files to create/modify; confirm approach matches existing conventions
3. **Implement** — write code following the patterns in section 3 below
4. **Validate** — run `php artisan test` and `php artisan pint` before marking done
5. **Update `MEMORY.md`** — document any new endpoints, models, decisions, or pitfalls

---

## 3. API Standards

### Adding a New Domain Endpoint
Follow this exact file structure (example domain: `Nutrition`):

```
app/Http/Controllers/Api/V1/Nutrition/NutritionLogController.php
app/Http/Requests/Api/V1/Nutrition/NutritionLog/NutritionLogIndexRequest.php
app/Http/Requests/Api/V1/Nutrition/NutritionLog/NutritionLogStoreRequest.php
app/Http/Resources/Api/V1/Nutrition/NutritionLogResource.php
```

### Response Format Rules
- **Always** use the `Response::` macros defined in `ResponseMacroProvider`. Never use `response()->json()` directly.
- `GET` list → `Response::success(new PaginationResource(...))`
- `POST` create → `Response::store(new XxxResource(...))`
- `PUT/PATCH` update → `Response::update(new XxxResource(...))`
- `DELETE` → `Response::destroy()`
- Not found → `Response::dataNotFound()`
- Forbidden → `Response::forbidden($code, $message)`

### Error Format Rules
- Use `SystemMessage` enum values for the `code` field — never hardcode integer codes.
- Validation errors are handled automatically by Laravel's `FormRequest` and return 422.
- Domain errors must use `Response::error(SystemMessage::SOME_CODE, 'message', $errors)`.

### Validation Rules
- All validation belongs in `FormRequest` classes, never inline in controllers.
- Use `authorize(): bool` — return `true` for open endpoints, implement policy checks for restricted ones.
- Numeric amounts (glucose, medication) must be validated as `numeric`.
- Date fields must be validated as `date`.

### Versioning
- All new API endpoints go under `routes/api.php` inside the appropriate group.
- Controller namespace: `App\Http\Controllers\Api\V1\{Domain}`.
- Resource namespace: `App\Http\Resources\Api\V1\{Domain}`.
- Request namespace: `App\Http\Requests\Api\V1\{Domain}\{Model}`.

---

## 4. Safety Rules

- **Do not introduce breaking changes** to existing response shapes — the frontend depends on the current structure.
- **Do not hard-delete** any records. All domain models use `SoftDeletes`; new models must too.
- **Do not apply `EnsureEmailIsVerified` middleware** to existing routes without explicit confirmation — it is currently unused.
- **Do not change logout behavior** (token deletion scope) without explicit instruction.
- **Do not add new dependencies** (`composer require`) without user approval.
- **Do not modify migrations that have already been run** — create new migrations instead.

---

## 5. Model Conventions

- New models must extend `BaseModel` (not `Model` directly), which provides request-aware pagination.
- All models must declare `$fillable`, `$casts`, and `$table` explicitly.
- All models must use `SoftDeletes`.
- Relationships use snake_case method names matching the table name (e.g., `glucose_logs()`).
- After creating a migration, regenerate model docblocks: `php artisan code:models --model=YourModel`.

---

## 6. Memory Discipline

Update `MEMORY.md` immediately after any of the following:

| Event | What to update in MEMORY.md |
|---|---|
| New endpoint added | Section 4 (API Endpoints) |
| New migration / table | Section 3 (Database Schema) |
| New model | Section 3 + model relationships |
| New Response macro | Section 2 (Response Macros) |
| New SystemMessage code | Section 2 (SystemMessage Enum Codes) |
| New business rule discovered | Section 5 (Business Rules) |
| New architectural decision | Section 6 (Known Decisions) |
| New external integration | Section 7 (External Integrations) |
| New pitfall found | Section 8 (Warnings / Pitfalls) |

---

## 7. Testing

- Test file location: `tests/Feature/` for HTTP tests, `tests/Unit/` for pure logic.
- Run tests: `composer test`
- Run code style fixer: `./vendor/bin/pint`
- Never skip failing tests — fix the root cause.
