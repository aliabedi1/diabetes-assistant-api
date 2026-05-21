<?php

namespace App\Http\Controllers\Api\V1\Medical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medical\MedicalLog\MedicalLogIndexRequest;
use App\Models\MedicalLog;
use Request;

class MedicalLogController extends Controller
{
    public function index(MedicalLogIndexRequest $request)
    {
        return $request->user()
            ->injectionLogs()
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'nullable|string',
            'logged_at' => 'required|date',
            'note' => 'nullable',
        ]);

        return MedicalLog::query()->create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);
    }
}
