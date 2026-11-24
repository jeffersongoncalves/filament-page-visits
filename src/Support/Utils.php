<?php

namespace JeffersonGoncalves\Filament\PageVisits\Support;

use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class Utils
{
    public static function getMetricsPageSlug(): string
    {
        return (string) config('filament-page-visits.metrics_page.slug', 'page-visits-metrics');
    }

    public static function isMetricsWidgetEnabled(string $key): bool
    {
        return (bool) config("filament-page-visits.metrics_page.widgets.{$key}", true);
    }

    /**
     * Keeps the audience page-visits:aggregate-and-prune counts in
     * visits_count and the daily breakdowns: no bots, no datacenter rows,
     * no crawler farms.
     *
     * @param  Builder<PageVisit>  $query
     */
    public static function onlyHumans(Builder $query): void
    {
        $query
            ->where('is_bot', false)
            ->where('is_datacenter', false)
            ->tap(self::excludeFarms(...));
    }

    /**
     * Drops rows whose user agent was a crawler farm (more than
     * farm_ua_ip_threshold distinct IPs) on any single day still in the raw
     * table — the per-day cut page-visits:aggregate-and-prune applies, so
     * all-time widgets match the daily stats. Rows with a null hash are kept.
     *
     * ponytail: one grouped scan of the raw table per call; precompute in the
     * daily stats if the raw table grows too big for a dashboard query.
     *
     * @param  Builder<PageVisit>  $query
     */
    public static function excludeFarms(Builder $query): void
    {
        $threshold = config('page-visits.farm_ua_ip_threshold');

        if (! is_numeric($threshold) || (int) $threshold <= 0) {
            return;
        }

        // Per day, not across the whole window: a real browser UA easily
        // crosses the threshold over months of traffic.
        $farm = PageVisit::query()
            ->whereNotNull('user_agent_hash')
            ->whereNotNull('ip_hash')
            ->groupByRaw('DATE(visited_at), user_agent_hash')
            ->havingRaw('count(distinct ip_hash) > ?', [(int) $threshold])
            ->pluck('user_agent_hash')
            ->unique()
            ->values();

        // whereNotIn alone would drop null hashes (NULL NOT IN … is null).
        $query->when($farm->isNotEmpty(), fn (Builder $q) => $q->where(
            fn (Builder $q) => $q->whereNull('user_agent_hash')->orWhereNotIn('user_agent_hash', $farm)
        ));
    }
}
