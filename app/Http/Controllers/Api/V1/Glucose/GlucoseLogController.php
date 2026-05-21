<?php

namespace App\Http\Controllers\Api\V1\Glucose;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogIndexRequest;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogStoreRequest;
use App\Http\Resources\Api\V1\Glucose\GlucoseLogResource;
use App\Http\Resources\PaginationResource;
use App\Models\GlucoseLog;
use Illuminate\Support\Facades\Response;

class GlucoseLogController extends Controller
{
    public function index(GlucoseLogIndexRequest $request)
    {
        return Response::success(
            data: new PaginationResource(
                GlucoseLogResource::collection(
                    $request->user()
                        ->glucoseLogs()
                        ->latest()
                        ->paginate()
                )
            )
        );
    }


    public function store(GlucoseLogStoreRequest $request)
    {
        return Response::store(
            new GlucoseLogResource(
                GlucoseLog::query()->create([
                    'user_id' => $request->user()->id,
                    ...$request->validated(),
                ])
            )
        );
    }
}
