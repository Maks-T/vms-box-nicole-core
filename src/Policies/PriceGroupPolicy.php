<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\PriceGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriceGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PriceGroup');
    }

    public function view(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('View:PriceGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PriceGroup');
    }

    public function update(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('Update:PriceGroup');
    }

    public function delete(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('Delete:PriceGroup');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PriceGroup');
    }

    public function restore(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('Restore:PriceGroup');
    }

    public function forceDelete(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('ForceDelete:PriceGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PriceGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PriceGroup');
    }

    public function replicate(AuthUser $authUser, PriceGroup $priceGroup): bool
    {
        return $authUser->can('Replicate:PriceGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PriceGroup');
    }

}