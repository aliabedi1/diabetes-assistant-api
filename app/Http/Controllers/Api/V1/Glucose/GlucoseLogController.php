<?php

namespace App\Http\Controllers\Api\V1\Glucose;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogIndexRequest;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogStoreRequest;
use App\Http\Resources\Api\V1\Glucose\GlucoseLogResource;
use App\Http\Resources\PaginationResource;
use App\Models\GlucoseLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class GlucoseLogController extends Controller
{
    public function index(GlucoseLogIndexRequest $request)
    {
        $userId = $request->user()->id;

        $paginator = Cache::remember(
            "logs:user:{$userId}",
            now()->addHour(),
            fn () => $request->user()
                ->glucose_logs()
                ->latest('logged_at')
                ->paginate()
        );

        return Response::success(
            data: new PaginationResource(
                GlucoseLogResource::collection($paginator)
            )
        );
    }


    public function store(GlucoseLogStoreRequest $request)
    {
        $log = GlucoseLog::query()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        Cache::forget("logs:user:{$request->user()->id}");

        return Response::store(new GlucoseLogResource($log));
    }
}
