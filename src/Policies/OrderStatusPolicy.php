<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\OrderStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrderStatus');
    }

    public function view(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('View:OrderStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrderStatus');
    }

    public function update(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('Update:OrderStatus');
    }

    public function delete(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('Delete:OrderStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrderStatus');
    }

    public function restore(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('Restore:OrderStatus');
    }

    public function forceDelete(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('ForceDelete:OrderStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrderStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrderStatus');
    }

    public function replicate(AuthUser $authUser, OrderStatus $orderStatus): bool
    {
        return $authUser->can('Replicate:OrderStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrderStatus');
    }

}