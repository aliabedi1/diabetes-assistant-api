<?php

namespace App\Http\Controllers\Api\V1\Medical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medical\MedicalLog\MedicalLogIndexRequest;
use App\Http\Requests\Api\V1\Medical\MedicalLog\MedicalLogStoreRequest;
use App\Http\Resources\Api\V1\Medical\MedicalLogResource;
use App\Http\Resources\PaginationResource;
use App\Models\MedicalLog;
use Illuminate\Support\Facades\Response;

class MedicalLogController extends Controller
{
    public function index(MedicalLogIndexRequest $request)
    {
        return Response::success(
            data: new PaginationResource(
                MedicalLogResource::collection(
                    $request->user()
                        ->medical_logs()
                        ->latest()
                        ->paginate()
                )
            )
        );
    }


    public function store(MedicalLogStoreRequest $request)
    {
        return Response::store(
            new MedicalLogResource(
                MedicalLog::query()->create([
                    'user_id' => $request->user()->id,
                    ...$request->validated(),
                ])
            )
        );
    }
}
