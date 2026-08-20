<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Invoicing\Entities\TimeEntry;

/** "Meine verrechenbaren Stunden pro Woche" — personal (not admin-only,
 * unlike the team widget), a trend of the caller's own billable hours
 * over the last N Monday-start weeks. No chart library exists anywhere
 * in this project, so this reuses the same CSS proportional-bar pattern
 * as every other "Diagramm"-style widget here. */
class MyWeeklyBillableHoursWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Invoicing\Entities\TimeEntry');
    }

    public static function render(User $user, string $size): string
    {
        $weeks = ['small' => 4, 'medium' => 8, 'large' => 12][$size] ?? 8;

        $rows = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $hours = (float) TimeEntry::where('user_id', $user->id)
                ->where('billable', true)
                ->whereBetween('entry_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->sum('hours');

            $rows[] = ['label' => 'KW'.$weekStart->format('W'), 'hours' => $hours];
        }

        $max = max(1, ...array_column($rows, 'hours'));

        $html = '<div class="dash-bars">';
        foreach ($rows as $row) {
            $pct = max(4, round($row['hours'] / $max * 100));
            $html .= '<div class="dash-bar-row">'
                .'<span class="dash-bar-day">'.e($row['label']).'</span>'
                .'<span class="dash-bar-track"><span class="dash-bar-fill" style="width:'.$pct.'%"></span></span>'
                .'<span class="dash-bar-value">'.TimeEntry::formatHours($row['hours']).'</span>'
                .'</div>';
        }
        $html .= '</div>';
        $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.report_week').'">'.e(__('Week Report')).' &rarr;</a>';

        return $html;
    }
}
