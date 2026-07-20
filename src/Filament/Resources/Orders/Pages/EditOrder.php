<?php

namespace Nicole\Box\Core\Filament\Resources\Orders\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\Orders\OrderResource;

class EditOrder extends EditRecord
{
  protected static string $resource = OrderResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('open_in_calculator')
        ->label(__('Open in Calculator'))
        ->icon('heroicon-o-calculator')
        ->color('success')
        ->url(fn (): string => route('calculator.show', ['code' => $this->record->code]))
        ->openUrlInNewTab(),

      Action::make('view_html')
        ->label(__('View'))
        ->icon('heroicon-o-eye')
        ->color('gray')
        ->url(fn (): string => "/api/v1/orders/{$this->record->code}/html")
        ->openUrlInNewTab(),


      Action::make('print_pdf')
        ->label(__('PDF'))
        ->icon('heroicon-o-document-text')
        ->color('info')
        ->url(fn (): string => "/api/v1/orders/{$this->record->code}/pdf")
        ->openUrlInNewTab(),

     ];

  }
}
