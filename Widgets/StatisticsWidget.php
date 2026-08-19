<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Invoicing\Entities\InternalHours;
use Modules\Invoicing\Entities\PresenceTime;
use Modules\Invoicing\Entities\TimeEntry;

/** "Statistiken" — this month's captured-hours breakdown. No chart
 * library exists anywhere in this project (confirmed by research), so
 * this is a simple proportional-bar list rather than a canvas chart,
 * matching the low-JS-dependency convention every other module follows. */
class StatisticsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Invoicing\Entities\TimeEntry');
    }

    public static function render(User $user, string $size): string
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $range = [$monthStart->toDateString(), $monthEnd->toDateString()];

        $billable = (float) TimeEntry::where('user_id', $user->id)->where('billable', true)->whereBetween('entry_date', $range)->sum('hours');

        if ($size === 'small') {
            $html = '<div class="dash-stat-row"><div class="dash-stat"><span class="dash-stat-value">'.TimeEntry::formatHours($billable).'</span><span class="dash-stat-label">'.e(__('Billable')).' — '.e(__('This Month')).'</span></div></div>';
            $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.report_month').'">'.e(__('Statistics')).' &rarr;</a>';

            return $html;
        }

        $nonBillable = (float) TimeEntry::where('user_id', $user->id)->where('billable', false)->whereBetween('entry_date', $range)->sum('hours');
        $internal = (float) InternalHours::where('user_id', $user->id)->whereBetween('entry_date', $range)->sum('hours');

        $rows = [
            [__('Billable'), $billable],
            [__('Non-billable'), $nonBillable],
            [__('Internal'), $internal],
        ];

        if ($size === 'large') {
            $presence = (float) PresenceTime::where('user_id', $user->id)->whereBetween('entry_date', $range)->get()->sum('hours');
            $rows[] = [__('Presence Time'), $presence];
        }

        $max = max(1, ...array_column($rows, 1));

        $html = '<div class="dash-bars">';
        foreach ($rows as [$label, $value]) {
            $pct = round($value / $max * 100);
            $html .= '<div class="dash-bar-row">'
                .'<span class="dash-bar-day">'.e($label).'</span>'
                .'<span class="dash-bar-track"><span class="dash-bar-fill" style="width:'.$pct.'%"></span></span>'
                .'<span class="dash-bar-value">'.TimeEntry::formatHours($value).'</span>'
                .'</div>';
        }
        $html .= '</div>';
        $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.report_month').'">'.e(__('Statistics')).' &rarr;</a>';

        return $html;
    }
}
