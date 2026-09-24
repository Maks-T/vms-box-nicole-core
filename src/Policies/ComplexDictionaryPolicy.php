<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\ComplexDictionary;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplexDictionaryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ComplexDictionary');
    }

    public function view(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('View:ComplexDictionary');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ComplexDictionary');
    }

    public function update(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('Update:ComplexDictionary');
    }

    public function delete(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('Delete:ComplexDictionary');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ComplexDictionary');
    }

    public function restore(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('Restore:ComplexDictionary');
    }

    public function forceDelete(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('ForceDelete:ComplexDictionary');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ComplexDictionary');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ComplexDictionary');
    }

    public function replicate(AuthUser $authUser, ComplexDictionary $complexDictionary): bool
    {
        return $authUser->can('Replicate:ComplexDictionary');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ComplexDictionary');
    }

}