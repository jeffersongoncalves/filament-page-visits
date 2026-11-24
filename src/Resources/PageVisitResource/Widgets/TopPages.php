<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\Filament\PageVisits\Support\Utils;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class TopPages extends TableWidget
{
    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var Builder<PageVisit> $query */
        $query = PageVisit::query()
            ->tap(Utils::onlyHumans(...))
            ->select(['path', DB::raw('max(id) as id'), DB::raw('count(*) as visits_count')])
            ->groupBy('path')
            ->orderByDesc('visits_count')
            ->limit(10);

        return $table
            ->heading(__('filament-page-visits::resources/page-visit.stats.top_pages'))
            ->query($query)
            // Filament appends its own ORDER BY <table>.id tiebreaker for a
            // deterministic sort unless told not to — the raw column
            // reference isn't in this grouped query's GROUP BY (path) or an
            // aggregate, which Postgres rejects outright (MySQL/SQLite
            // silently tolerate it, which is why this only surfaced in
            // production): "column page_visits.id must appear in the GROUP
            // BY clause or be used in an aggregate function".
            ->defaultKeySort(false)
            ->paginated(false)
            ->columns([
                TextColumn::make('path')
                    ->label(__('filament-page-visits::resources/page-visit.fields.path'))
                    ->limit(60),

                TextColumn::make('visits_count')
                    ->label(__('filament-page-visits::resources/page-visit.stats.total_visits'))
                    ->badge(),
            ]);
    }
}
