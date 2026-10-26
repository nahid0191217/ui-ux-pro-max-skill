<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Admin and Staff can view any request; Patients can view only their own.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->canManagePortal() || $serviceRequest->user_id === $user->id;
    }

    /**
     * Only Admin, Staff, or the owning Patient may download private medical reports.
     */
    public function downloadMedicalReport(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->canManagePortal() || $serviceRequest->user_id === $user->id;
    }

    /**
     * Only Admin and Staff can update statuses and workflow notes.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->canManagePortal();
    }
}
