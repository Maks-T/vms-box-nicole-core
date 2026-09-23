<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\Pipeline;
use Illuminate\Auth\Access\HandlesAuthorization;

class PipelinePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pipeline');
    }

    public function view(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('View:Pipeline');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pipeline');
    }

    public function update(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('Update:Pipeline');
    }

    public function delete(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('Delete:Pipeline');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pipeline');
    }

    public function restore(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('Restore:Pipeline');
    }

    public function forceDelete(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('ForceDelete:Pipeline');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pipeline');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pipeline');
    }

    public function replicate(AuthUser $authUser, Pipeline $pipeline): bool
    {
        return $authUser->can('Replicate:Pipeline');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pipeline');
    }

}