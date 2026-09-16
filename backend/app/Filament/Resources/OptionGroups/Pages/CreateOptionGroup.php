<?php

namespace App\Filament\Resources\OptionGroups\Pages;

use App\Filament\Resources\OptionGroups\OptionGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOptionGroup extends CreateRecord
{
    protected static string $resource = OptionGroupResource::class;

    /** Go straight to the edit page so options can be added. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
