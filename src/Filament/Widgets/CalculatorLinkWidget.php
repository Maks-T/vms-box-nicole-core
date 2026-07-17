<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Widgets;

use Filament\Widgets\Widget;

class CalculatorLinkWidget extends Widget
{

  protected string $view = 'nicole-core::filament.widgets.calculator-link-widget';

  protected int|string|array $columnSpan = 'full';
}
