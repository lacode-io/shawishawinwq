<?php

namespace App\Policies;

use App\Models\AppNote;
use App\Models\User;

class AppNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_app_notes');
    }

    public function view(User $user, AppNote $appNote): bool
    {
        return $user->hasPermissionTo('view_app_notes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_app_notes');
    }

    public function update(User $user, AppNote $appNote): bool
    {
        return $user->hasPermissionTo('update_app_notes');
    }

    public function delete(User $user, AppNote $appNote): bool
    {
        return $user->hasPermissionTo('delete_app_notes');
    }
}
