<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\Filament\PageVisits\Support\Utils;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class TopReferrers extends TableWidget
{
    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var Builder<PageVisit> $query */
        $query = PageVisit::query()
            ->whereNotNull('referer_host')
            ->tap(Utils::onlyHumans(...))
            ->select(['referer_host', DB::raw('max(id) as id'), DB::raw('count(*) as visits_count')])
            ->groupBy('referer_host')
            ->orderByDesc('visits_count')
            ->limit(10);

        return $table
            ->heading(__('filament-page-visits::resources/page-visit.stats.top_referrers'))
            ->query($query)
            // Same Postgres GROUP BY constraint as TopPages — see that class.
            ->defaultKeySort(false)
            ->paginated(false)
            ->columns([
                TextColumn::make('referer_host')
                    ->label(__('filament-page-visits::resources/page-visit.fields.referer_host'))
                    ->limit(60),

                TextColumn::make('visits_count')
                    ->label(__('filament-page-visits::resources/page-visit.stats.total_visits'))
                    ->badge(),
            ]);
    }
}
