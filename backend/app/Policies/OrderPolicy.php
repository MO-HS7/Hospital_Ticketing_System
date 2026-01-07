<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Can user view any orders?
     */
    public function viewAny(User $user): bool
    {
        // Staff roles can view orders of their type
        // Admin/reception can view all
        // Patient can view (scoped in controller)
        return $user->hasRole(['doctor', 'lab_technician', 'radiologist', 'pharmacist', 'admin', 'reception', 'patient']);
    }

    /**
     * Can user view a specific order?
     */
    public function view(User $user, Order $order): bool
    {
        // Admin/reception can view all
        if ($user->hasRole(['admin', 'reception'])) {
            return true;
        }

        // Doctor can view orders they created or tickets they're assigned to
        if ($user->hasRole('doctor')) {
            return $order->ordered_by === $user->id ||
                   $order->ticket->assigned_to === $user->id;
        }

        // Staff can view orders of their type only
        if ($user->hasRole('lab_technician') && $order->type === 'lab') {
            return true;
        }
        if ($user->hasRole('radiologist') && $order->type === 'radiology') {
            return true;
        }
        if ($user->hasRole('pharmacist') && $order->type === 'pharmacy') {
            return true;
        }

        // Patient can view own encounter orders
        if ($user->hasRole('patient')) {
            return $order->encounter->patient_id === $user->id;
        }

        return false;
    }

    /**
     * Can user create orders?
     */
    public function create(User $user): bool
    {
        // Only doctors can create orders
        return $user->hasRole('doctor');
    }

    /**
     * Can user update an order?
     */
    public function update(User $user, Order $order): bool
    {
        // Admin can update any
        if ($user->hasRole('admin')) {
            return true;
        }

        // Staff can update orders of their type
        if ($user->hasRole('lab_technician') && $order->type === 'lab') {
            return true;
        }
        if ($user->hasRole('radiologist') && $order->type === 'radiology') {
            return true;
        }
        if ($user->hasRole('pharmacist') && $order->type === 'pharmacy') {
            return true;
        }

        return false;
    }
}
