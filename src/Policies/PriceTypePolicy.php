<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\PriceType;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriceTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PriceType');
    }

    public function view(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('View:PriceType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PriceType');
    }

    public function update(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('Update:PriceType');
    }

    public function delete(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('Delete:PriceType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PriceType');
    }

    public function restore(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('Restore:PriceType');
    }

    public function forceDelete(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('ForceDelete:PriceType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PriceType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PriceType');
    }

    public function replicate(AuthUser $authUser, PriceType $priceType): bool
    {
        return $authUser->can('Replicate:PriceType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PriceType');
    }

}