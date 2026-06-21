<?php

namespace App\Http\Controllers\Api\V1\Medicine;

use App\Enums\General\SystemMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medicine\Medicine\MedicineDestroyRequest;
use App\Http\Requests\Api\V1\Medicine\Medicine\MedicineIndexRequest;
use App\Http\Requests\Api\V1\Medicine\Medicine\MedicineRecentRequest;
use App\Http\Requests\Api\V1\Medicine\Medicine\MedicineStoreRequest;
use App\Http\Requests\Api\V1\Medicine\Medicine\MedicineUpdateRequest;
use App\Http\Resources\Api\V1\Medicine\MedicineResource;
use App\Models\MedicalLog;
use App\Models\Medicine;
use Illuminate\Support\Facades\Response;

class MedicineController extends Controller
{
    public function index(MedicineIndexRequest $request)
    {
        $userId = $request->user()->id;

        $medicines = Medicine::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('is_global', true);
            })
            ->orderByDesc('is_global')
            ->orderBy('name_en')
            ->get();

        return Response::success(MedicineResource::collection($medicines));
    }

    public function store(MedicineStoreRequest $request)
    {
        $userId = $request->user()->id;
        $name = $request->validated('name_en');

        $existing = Medicine::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(name_en) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            return Response::error(SystemMessage::DATA_EXIST, __('Medicine already exists.'));
        }

        $medicine = Medicine::create([
            'user_id' => $userId,
            'name_en' => $name,
            'name_fa' => $request->validated('name_fa'),
            'is_global' => false,
        ]);

        return Response::store(new MedicineResource($medicine));
    }

    public function update(MedicineUpdateRequest $request, Medicine $medicine)
    {
        if ($medicine->user_id !== $request->user()->id) {
            return Response::forbidden(SystemMessage::ACCESS_DENIED, __('You cannot edit this medicine.'));
        }

        $name = $request->validated('name_en');

        $duplicate = Medicine::query()
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $medicine->id)
            ->whereRaw('LOWER(name_en) = ?', [strtolower($name)])
            ->exists();

        if ($duplicate) {
            return Response::error(SystemMessage::DATA_EXIST, __('A medicine with this name already exists.'));
        }

        $medicine->update($request->validated());

        return Response::update(new MedicineResource($medicine->fresh()));
    }

    public function destroy(MedicineDestroyRequest $request, Medicine $medicine)
    {
        if ($medicine->user_id !== $request->user()->id) {
            return Response::forbidden(SystemMessage::ACCESS_DENIED, __('You cannot delete this medicine.'));
        }

        if ($medicine->medical_logs()->exists()) {
            return Response::error(
                SystemMessage::FAIL,
                __('Cannot delete a medicine that is referenced by existing logs.')
            );
        }

        $medicine->delete();

        return Response::destroy();
    }

    public function recent(MedicineRecentRequest $request)
    {
        $userId = $request->user()->id;

        $recent = MedicalLog::query()
            ->selectRaw('medicine_id, MAX(logged_at) as last_logged')
            ->where('user_id', $userId)
            ->whereNotNull('medicine_id')
            ->groupBy('medicine_id')
            ->orderByDesc('last_logged')
            ->limit(4)
            ->with('medicine')
            ->get()
            ->pluck('medicine')
            ->filter()
            ->values();

        return Response::success(MedicineResource::collection($recent));
    }
}
