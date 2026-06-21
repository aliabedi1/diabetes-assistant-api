<?php

namespace App\Http\Controllers\Api\V1\Medical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medical\MedicalLog\MedicalLogIndexRequest;
use App\Http\Requests\Api\V1\Medical\MedicalLog\MedicalLogStoreRequest;
use App\Http\Resources\Api\V1\Medical\MedicalLogResource;
use App\Http\Resources\PaginationResource;
use App\Models\MedicalLog;
use App\Models\Medicine;
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
                        ->with('medicine')
                        ->latest('logged_at')
                        ->paginate()
                )
            )
        );
    }

    public function store(MedicalLogStoreRequest $request)
    {
        $userId = $request->user()->id;

        // medicine_id takes priority; medicine_name is only used when medicine_id is absent.
        if ($request->filled('medicine_id')) {
            $medicineId = (int) $request->validated('medicine_id');
        } else {
            $name = $request->validated('medicine_name');
            $medicine = Medicine::query()
                ->where('user_id', $userId)
                ->whereRaw('LOWER(name_en) = ?', [strtolower($name)])
                ->first();

            if (! $medicine) {
                $medicine = Medicine::create([
                    'user_id' => $userId,
                    'name_en' => $name,
                    'is_global' => false,
                ]);
            }

            $medicineId = $medicine->id;
        }

        $log = MedicalLog::create([
            'user_id' => $userId,
            'medicine_id' => $medicineId,
            'amount' => $request->validated('amount'),
            'logged_at' => $request->validated('logged_at') ?? now(),
            'note' => $request->validated('note'),
        ]);

        $log->load('medicine');

        return Response::store(new MedicalLogResource($log));
    }
}
