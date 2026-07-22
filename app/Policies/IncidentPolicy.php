<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determine if the user can view the incident.
     * Agencies can view if they have any assignment (past or active).
     */
    public function view(User $user, Incident $incident): bool
    {
        // Admin users can always view
        if ($user->isAdministrator()) {
            return true;
        }

        // Agency users can view if they have any assignment on this incident
        if ($user->isAgency() && $user->agency_id) {
            return $incident->currentAssignments()
                ->where('agency_id', $user->agency_id)
                ->exists();
        }

        // Personnel users can view if they are assigned to this incident
        if ($user->isPersonnel()) {
            return $incident->currentAssignments()
                ->where('assigned_to', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can update the incident status.
     * Only agencies with an active assignment can update status.
     */
    public function updateStatus(User $user, Incident $incident): bool
    {
        if ($user->isAgency() && $user->agency_id) {
            return $incident->currentAssignments()
                ->where('agency_id', $user->agency_id)
                ->where('is_active', true)
                ->exists();
        }

        if ($user->isPersonnel()) {
            return $incident->currentAssignments()
                ->where('assigned_to', $user->id)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can request documents for this incident.
     * Only agencies with any assignment can request documents.
     */
    public function requestDocuments(User $user, Incident $incident): bool
    {
        if ($user->isAgency() && $user->agency_id) {
            return $incident->currentAssignments()
                ->where('agency_id', $user->agency_id)
                ->exists();
        }

        if ($user->isPersonnel()) {
            return $incident->currentAssignments()
                ->where('assigned_to', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can resolve this incident.
     * Only the assigned agency can submit resolution.
     */
    public function submitResolution(User $user, Incident $incident): bool
    {
        if ($user->isAgency() && $user->agency_id) {
            return $incident->currentAssignments()
                ->where('agency_id', $user->agency_id)
                ->where('is_active', true)
                ->exists();
        }

        if ($user->isPersonnel()) {
            return $incident->currentAssignments()
                ->where('assigned_to', $user->id)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }
}
