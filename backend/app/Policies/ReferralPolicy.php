<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    /**
     * Determine if the user can view referrals.
     */
    public function viewAny(User $user): bool
    {
        // Doctors, reception, and admin can view referrals
        return $user->hasRole(['doctor', 'reception', 'admin']);
    }

    /**
     * Determine if the user can view a specific referral.
     */
    public function view(User $user, Referral $referral): bool
    {
        // Admin and reception can view all
        if ($user->hasRole(['admin', 'reception'])) {
            return true;
        }

        // Doctor can view if they're the referrer or assigned to either ticket
        if ($user->hasRole('doctor')) {
            return $referral->referred_by === $user->id ||
                   $referral->fromTicket->assigned_to === $user->id ||
                   ($referral->toTicket && $referral->toTicket->assigned_to === $user->id);
        }

        return false;
    }

    /**
     * Determine if the user can create a referral.
     */
    public function create(User $user): bool
    {
        // Only doctors can create referrals
        return $user->hasRole('doctor');
    }
}
