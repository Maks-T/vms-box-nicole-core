<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\ProductFamily;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductFamilyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductFamily');
    }

    public function view(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('View:ProductFamily');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductFamily');
    }

    public function update(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('Update:ProductFamily');
    }

    public function delete(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('Delete:ProductFamily');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductFamily');
    }

    public function restore(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('Restore:ProductFamily');
    }

    public function forceDelete(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('ForceDelete:ProductFamily');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductFamily');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductFamily');
    }

    public function replicate(AuthUser $authUser, ProductFamily $productFamily): bool
    {
        return $authUser->can('Replicate:ProductFamily');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductFamily');
    }

}