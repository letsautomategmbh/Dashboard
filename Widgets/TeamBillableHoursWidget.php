<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Invoicing\Entities\TimeEntry;

/** "Mitarbeiter" — admin-only (comparative per-person data, same
 * reasoning as open_invoices/bexio_sync_status), this month's billable
 * hours per active staff member, avatar + number + relative bar,
 * ranked highest first. */
class TeamBillableHoursWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && class_exists('Modules\Invoicing\Entities\TimeEntry');
    }

    public static function render(User $user, string $size): string
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $rows = User::where('status', User::STATUS_ACTIVE)->get()->map(function ($person) use ($monthStart, $monthEnd) {
            $hours = (float) TimeEntry::where('user_id', $person->id)
                ->where('billable', true)
                ->whereBetween('entry_date', [$monthStart, $monthEnd])
                ->sum('hours');

            return ['user' => $person, 'hours' => $hours];
        })->sortByDesc('hours')->values();

        $limit = ['small' => 3, 'medium' => 6, 'large' => 10][$size] ?? 6;
        $rows = $rows->take($limit);

        if ($rows->isEmpty()) {
            return '<div class="dash-empty-inline">'.e(__('No data yet.')).'</div>';
        }

        $max = max(1, (float) $rows->max('hours'));

        $html = '<div class="dash-people">';
        foreach ($rows as $row) {
            $pct = max(4, round($row['hours'] / $max * 100));
            $html .= '<div class="dash-person-row">'
                .'<img class="dash-person-avatar" src="'.e($row['user']->getPhotoUrl()).'" alt="">'
                .'<div class="dash-person-main">'
                .'<span class="dash-person-name">'.e($row['user']->getFullName()).'</span>'
                .'<span class="dash-bar-track"><span class="dash-bar-fill" style="width:'.$pct.'%"></span></span>'
                .'</div>'
                .'<span class="dash-person-value">'.TimeEntry::formatHours($row['hours']).'</span>'
                .'</div>';
        }
        $html .= '</div>';
        $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.report_month').'">'.e(__('Statistics')).' &rarr;</a>';

        return $html;
    }
}
