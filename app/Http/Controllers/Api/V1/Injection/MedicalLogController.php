<?php

namespace App\Http\Controllers\Api\V1\Injection;

use App\Http\Controllers\Controller;
use Request;

class MedicalLogController extends Controller
{
    public function index(Request $request)
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

        return InjectionLog::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);
    }
}
