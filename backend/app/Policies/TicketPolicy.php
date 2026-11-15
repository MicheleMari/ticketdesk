<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_SUPERVISOR,
            User::ROLE_REQUESTER,
        ], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->company_id !== $ticket->company_id) {
            return false;
        }

        return match ($user->role) {
            User::ROLE_ADMIN,
            User::ROLE_SUPERVISOR => true,
            User::ROLE_REQUESTER => $ticket->creator_id === $user->id,
            default => false,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->company_id !== $ticket->company_id) {
            return false;
        }

        if ($ticket->status === 'closed') {
            return false;
        }

        return match ($user->role) {
            User::ROLE_ADMIN => true,

            User::ROLE_SUPERVISOR => $ticket->assignee_id === null
                || $ticket->assignee_id === $user->id,

            User::ROLE_REQUESTER => $ticket->creator_id === $user->id,

            default => false,
        };
    }

    /**
     * Determine whether the user can reopen the model.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->company_id === $ticket->company_id
            && $user->role === User::ROLE_ADMIN
            && $ticket->status === 'closed';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->company_id === $ticket->company_id
            && $user->role === User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }
}