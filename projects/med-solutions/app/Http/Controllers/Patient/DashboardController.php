<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display authenticated patient's service requests, visa/appointment status, and video links.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $serviceRequests = ServiceRequest::with('assignedStaff')
            ->where('user_id', $user->id)
            ->orWhere(fn ($q) => $user->phone ? $q->where('phone', $user->phone) : $q)
            ->latest()
            ->paginate(10);

        $consultations = Consultation::query()
            ->where('user_id', $user->id)
            ->orWhere(fn ($q) => $user->phone ? $q->where('phone', $user->phone) : $q)
            ->orderByDesc('preferred_date')
            ->get();

        return view('patient.dashboard', compact('user', 'serviceRequests', 'consultations'));
    }
}
