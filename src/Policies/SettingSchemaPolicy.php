<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Nicole\Box\Core\Models\SettingSchema;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingSchemaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SettingSchema');
    }

    public function view(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('View:SettingSchema');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SettingSchema');
    }

    public function update(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('Update:SettingSchema');
    }

    public function delete(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('Delete:SettingSchema');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SettingSchema');
    }

    public function restore(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('Restore:SettingSchema');
    }

    public function forceDelete(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('ForceDelete:SettingSchema');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SettingSchema');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SettingSchema');
    }

    public function replicate(AuthUser $authUser, SettingSchema $settingSchema): bool
    {
        return $authUser->can('Replicate:SettingSchema');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SettingSchema');
    }

}