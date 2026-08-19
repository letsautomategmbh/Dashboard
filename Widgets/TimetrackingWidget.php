<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Invoicing\Entities\TimeEntry;

/** "Zeiterfassung" — today's and this week's captured hours, plus (medium/
 * large) a per-day bar against target hours. Target hours come from the
 * same cross-module extension point Invoicing's own Übersicht already
 * uses (Eventy::filter('invoicing.time_tracking.target_hours', ...),
 * answered by Hr's HolidayBalance if that module is installed, a safe
 * 0.0 default otherwise) — this widget never queries Hr directly. */
class TimetrackingWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Invoicing\Entities\TimeEntry');
    }

    public static function render(User $user, string $size): string
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $todayHours = (float) TimeEntry::where('user_id', $user->id)
            ->whereDate('entry_date', $today)
            ->sum('hours');

        $weekHours = (float) TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('hours');

        $html = '<div class="dash-stat-row">'
            .'<div class="dash-stat"><span class="dash-stat-value">'.TimeEntry::formatHours($todayHours).'</span><span class="dash-stat-label">'.e(__('Today')).'</span></div>';

        if ($size !== 'small') {
            $html .= '<div class="dash-stat"><span class="dash-stat-value">'.TimeEntry::formatHours($weekHours).'</span><span class="dash-stat-label">'.e(__('This Week')).'</span></div>';
        }
        $html .= '</div>';

        if ($size === 'large') {
            $html .= '<div class="dash-bars">';
            for ($i = 0; $i < 7; $i++) {
                $day = $weekStart->copy()->addDays($i);
                $captured = (float) TimeEntry::where('user_id', $user->id)->whereDate('entry_date', $day)->sum('hours');
                $target = (float) \Eventy::filter('invoicing.time_tracking.target_hours', 0.0, $user->id, $day->toDateString());
                $pct = $target > 0 ? min(100, round($captured / $target * 100)) : ($captured > 0 ? 100 : 0);
                $html .= '<div class="dash-bar-row">'
                    .'<span class="dash-bar-day">'.e($day->format('D')).'</span>'
                    .'<span class="dash-bar-track"><span class="dash-bar-fill" style="width:'.$pct.'%"></span></span>'
                    .'<span class="dash-bar-value">'.TimeEntry::formatHours($captured).'</span>'
                    .'</div>';
            }
            $html .= '</div>';
        }

        $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.overview').'">'.e(__('View your timesheet')).' &rarr;</a>';

        return $html;
    }
}
