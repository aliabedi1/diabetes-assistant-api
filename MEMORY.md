# MEMORY.md — Diabetes Assistant API

> Single source of truth for project knowledge. Update this file after every meaningful change.

---

## 1. Architecture Overview

### System Design
Laravel 13.8 monolith, PHP 8.3, serving a pure REST JSON API. No web views or Blade templates. Authentication via Laravel Sanctum (token-based). Frontend is a separate React project (`diabetes-assistant-frontend`).

### Folder Structure
```
app/
  Console/Commands/           — Artisan commands (DebugCommand)
  Enums/General/              — System-wide enums (SystemMessage)
  Http/
    Controllers/Api/V1/       — Versioned API controllers, grouped by domain
    Middleware/               — Custom middleware (EnsureEmailIsVerified)
    Requests/Api/V1/          — FormRequests, one per action per domain
    Resources/Api/V1/         — API Resources (JSON transformers), one per model per domain
  Models/                     — Eloquent models (User, GlucoseLog, MedicalLog, Role, UserRole, BaseModel, Authenticatable)
  Providers/                  — AppServiceProvider, ResponseMacroProvider
  Traits/                     — EnumTools
database/
  migrations/                 — All schema migrations
  seeders/                    — DatabaseSeeder, RoleSeeder
routes/
  api.php                     — All API routes (public + sanctum-protected groups)
```

### Request Lifecycle
1. Request hits `routes/api.php`
2. Passes through `auth:sanctum` middleware (where required)
3. Resolved by FormRequest (authorization + validation)
4. Controller method calls Model / Response macro
5. Resource transforms data
6. Response macro wraps in standard envelope

---

## 2. Conventions

### Naming
| Layer | Convention | Example |
|---|---|---|
| Controller | `{Domain}Controller` in `Api/V1/{Domain}/` | `GlucoseLogController` |
| FormRequest | `{Model}{Action}Request` in `Requests/Api/V1/{Domain}/{Model}/` | `GlucoseLogStoreRequest` |
| Resource | `{Model}Resource` in `Resources/Api/V1/{Domain}/` | `GlucoseLogResource` |
| Model | PascalCase singular | `GlucoseLog` |
| DB table | snake_case plural | `glucose_logs` |
| Relationships | snake_case method names | `glucose_logs()`, `medical_logs()` |

### Coding Style
- All models use `SoftDeletes`
- All models extend `BaseModel` (except `User` which extends `Authenticatable`)
- No service layer currently — logic lives directly in controllers
- `reliese/laravel` generates model property docblocks — keep them in sync after migrations
- `laravel/pint` is the code style tool

### API Response Format

#### Success (200 / 201 / 202)
```json
{
  "code": 1,
  "message": "...",
  "data": { ... }
}
```

#### Error (4xx / 5xx)
```json
{
  "code": <int SystemMessage>,
  "message": "...",
  "errors": { }
}
```

#### Paginated List
`data` is a `PaginationResource` with this shape:
```json
{
  "items": [ ... ],
  "pagination": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Response Macros (defined in `ResponseMacroProvider`)
| Macro | HTTP status | Use for |
|---|---|---|
| `Response::success($data, $code, $msg, $status)` | 200 | General success |
| `Response::store($data, $msg)` | 201 | Resource created |
| `Response::update($data, $msg)` | 202 | Resource updated |
| `Response::destroy($msg)` | 202 | Resource deleted |
| `Response::error($code, $msg, $errors, $status)` | 400 | Generic error |
| `Response::dataNotFound($errors)` | 404 | Not found |
| `Response::forbidden($code, $msg, $errors)` | 403 | Forbidden |
| `Response::badGateway($code, $msg, $errors)` | 502 | External service failure |

### SystemMessage Enum Codes
| Case | Value | Meaning |
|---|---|---|
| `SUCCESS` | 1 | OK |
| `FAIL` | 0 | General failure |
| `INTERNAL_ERROR` | 10 | Unexpected server error |
| `DATA_NOT_FOUND` | 11 | Entity not found |
| `PAGE_NOT_FOUND` | 12 | Route not found |
| `BAD_DATA` | 13 | Validation / bad input |
| `DATA_EXIST` | 100 | Duplicate record |
| `USER_NOT_FOUND` | 101 | User not found |
| `USER_IS_BLOCKED` | 102 | User blocked |
| `ACCESS_DENIED` | 103 | Access denied |

---

## 3. Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| username | string | unique |
| name | string | |
| last_name | string | |
| email | string | unique |
| email_verified_at | datetime | nullable |
| password | string | bcrypt hashed |
| remember_token | string | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | softDelete | |

### `roles`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | e.g. "Admin", "User" |
| slug | string | unique; e.g. "admin", "user" |
| timestamps + softDeletes | | |

### `user_roles` (pivot)
| Column | Type |
|---|---|
| id | bigint PK |
| user_id | FK → users |
| role_id | FK → roles |
| timestamps + deleted_at | |

### `glucose_logs`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | cascade delete |
| glucose_amount | decimal(8,2) | |
| logged_at | dateTime | user-provided timestamp |
| note | text | nullable |
| timestamps + softDeletes | | |

### `medical_logs`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | cascade delete |
| amount | decimal(8,2) | |
| type | string | nullable (e.g. insulin type) |
| logged_at | dateTime | user-provided timestamp |
| note | text | nullable |
| timestamps + softDeletes | | |

### `medicines`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | nullable; null = global medicine |
| name_en | string | |
| name_fa | string | nullable; Persian label |
| is_global | boolean | default false |
| timestamps + softDeletes | | |
| UNIQUE | (user_id, name_en) | case-insensitive via MySQL collation |

### `medical_logs` (updated)
Added `medicine_id` (nullable FK → medicines.id, RESTRICT on delete). Old `type` column kept for backward compatibility.

### `personal_access_tokens`
Standard Sanctum table.

---

## 4. API Endpoints

Base URL: `http://localhost:8000/api`

### Public (no auth)
| Method | Path | Controller#action |
|---|---|---|
| POST | `/auth/register` | `AuthController@register` |
| POST | `/auth/login` | `AuthController@login` |

### Protected (Bearer token via Sanctum)
| Method | Path | Controller#action |
|---|---|---|
| GET | `/auth/me` | `AuthController@me` |
| POST | `/auth/logout` | `AuthController@logout` |
| GET | `/glucose/logs` | `GlucoseLogController@index` |
| POST | `/glucose/logs` | `GlucoseLogController@store` |
| GET | `/medical/logs` | `MedicalLogController@index` |
| POST | `/medical/logs` | `MedicalLogController@store` |
| GET | `/medicines` | `MedicineController@index` |
| POST | `/medicines` | `MedicineController@store` |
| GET | `/medicines/recent` | `MedicineController@recent` |
| PUT | `/medicines/{medicine}` | `MedicineController@update` |
| DELETE | `/medicines/{medicine}` | `MedicineController@destroy` |

### Pagination Query Param
All list endpoints support `?per_page=N` (default 15, max 100).

---

## 5. Business Rules

1. **Registration**: new users are automatically assigned the `user` role (slug `user`).
2. **Login**: the `login` field accepts either `email` or `username`.
3. **Logout**: deletes all tokens for the user, not just the current one.
4. **Data scoping**: all logs are scoped to `$request->user()` — no cross-user access.
5. **Soft deletes**: all domain entities use soft deletes; nothing is hard-deleted.
6. **Pagination**: `BaseModel::getPerPage()` reads `?per_page` from request, enforces max 100.
7. **Medical log type**: free-form nullable string kept for backward compatibility; new logs use `medicine_id` instead.
8. **Medicine ownership**: `user_id = null` means global/platform medicine (`is_global = true`). User-created medicines have `user_id` set. Global medicines are visible to all users but cannot be edited or deleted via API.
9. **Medicine log store**: if both `medicine_id` and `medicine_name` are sent, `medicine_id` wins. If only `medicine_name` is sent, a user-owned medicine is found-or-created (case-insensitive lookup via `LOWER(name_en)`).
10. **Medicine delete**: blocked at app level (422) if any `medical_logs` reference the medicine. FK is `RESTRICT` as a DB-level safety net.

---

## 6. Known Decisions

- **No service layer yet**: business logic is in controllers. This is intentional for the current scope — keep it simple until complexity warrants it.
- **Sanctum over Passport**: lightweight token auth is sufficient for this SPA/mobile use case.
- **`ResponseMacroProvider`** standardizes all response envelopes across the app — always use it, never return raw `response()->json()`.
- **`BaseModel` pagination cap**: max 100 per page is enforced at the model layer, not at the request layer.
- **Migration naming mismatch**: the migration file `create_injection_logs_table.php` actually creates the `medical_logs` table. The file was renamed mid-development. This is a known inconsistency — do not rename the migration file.
- **Schema::defaultStringLength(191)**: set in `AppServiceProvider` for MySQL utf8mb4 compatibility.
- **Frontend URL config**: `config('app.frontend_url')` is used for password reset links.
- **Relationship naming**: User model defines relationships in snake_case (`glucose_logs()`, `medical_logs()`). Always call them as `->glucose_logs()` in code — not camelCase. Laravel's magic property access (`$user->glucoseLogs`) works, but direct method calls must match the defined name.
- **Glucose logs cache**: `GET /glucose/logs` caches the paginator per user under key `logs:user:{id}` with a 24-hour TTL. The cache is busted immediately on `POST /glucose/logs`. Sort by `logged_at` ASC (`orderBy('logged_at')`) so chronological order drives chart display. Do not use `latest()` or sort by `created_at`.
- **`logged_at` default**: if the client omits `logged_at` on `POST /glucose/logs`, `prepareForValidation()` fills it with `now()` before the `required|date` rule runs.
- **No dedicated `/logs` endpoint**: the frontend chart uses `GET /glucose/logs` directly (via `getGlucoseLogs()` service). There is no separate `/logs` route and none is needed — chart filtering is done client-side from the paginated response.

---

## 7. External Integrations

None currently. The API is self-contained. No third-party services, queues, or external APIs are integrated.

---

## 8. Warnings / Pitfalls

- The `medical_logs` migration file is named `create_injection_logs_table.php` — do not rename it or re-run migrations expecting the table name from the file name.
- `UserResource` has commented-out fields (`default_address`, `projects`) — don't uncomment without implementing the relations.
- `logout` wipes **all** user tokens. If multi-device support is needed in future, change to `$request->user()->currentAccessToken()->delete()`.
- There is no `show`, `update`, or `destroy` endpoint for glucose or medical logs yet. Do not assume CRUD is complete.
- `EnsureEmailIsVerified` middleware exists but is **not applied** to any route yet.
- `DebugCommand` exists in `Console/Commands` — verify it is safe before production deploy.
