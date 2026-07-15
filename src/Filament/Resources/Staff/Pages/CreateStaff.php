<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Staff\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Staff\StaffResource;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;
}
