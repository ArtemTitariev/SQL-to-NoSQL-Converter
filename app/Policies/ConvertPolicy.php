<?php

namespace App\Policies;

use App\Models\Convert;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConvertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }
    
    public function executeStep(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }
    
    public function resume(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }
    
    public function process(User $user, Convert $convert): bool
    {
        return $this->check($user, $convert);
    }

    private function check(User $user, Convert $convert): bool
    {
        return $convert->user_id === $user->id;
    }
}
