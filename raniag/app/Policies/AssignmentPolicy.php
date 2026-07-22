<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Determine if the user can view the assignment.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        // Admin can view any assignment
        if ($user->isAdministrator()) {
            return true;
        }

        // Agency can view their own assignments
        if ($user->isAgency() && $user->agency_id === $assignment->agency_id) {
            return true;
        }

        if ($user->isPersonnel() && $user->id === $assignment->assigned_to) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can complete the assignment.
     * Only the assigned agency can complete their own assignment.
     */
    public function complete(User $user, Assignment $assignment): bool
    {
        if ($user->isAgency()) {
            if ($user->agency_id !== $assignment->agency_id) {
                return false;
            }

            return $assignment->is_active;
        }

        if ($user->isPersonnel()) {
            return $assignment->assigned_to === $user->id && $assignment->is_active;
        }

        return false;
    }
}
