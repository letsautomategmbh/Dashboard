<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Hr\Entities\AbsenceRequest;
use Modules\Hr\Entities\HolidayRequest;

/** "Abwesenheiten heute" — admin-only, who is currently out: both approved
 * HolidayRequest (Urlaub) and approved AbsenceRequest (Militär, Krankheit,
 * …) rows whose date range covers today. Deliberately combines both — from
 * an admin's "who's away today" view, the reason someone's out matters
 * less than the fact that they are; the separate leave_requests widget
 * covers the still-pending queue for both types. */
class AbsencesTodayWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && class_exists('Modules\Hr\Entities\HolidayRequest');
    }

    public static function render(User $user, string $size): string
    {
        $today = Carbon::today()->toDateString();

        $holidayRows = HolidayRequest::where('status', HolidayRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('user')
            ->get()
            ->map(function ($row) {
                return ['user' => $row->user, 'label' => __('Holiday Requests')];
            });

        $absenceRows = AbsenceRequest::where('status', AbsenceRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['user', 'absenceType'])
            ->get()
            ->map(function ($row) {
                return ['user' => $row->user, 'label' => $row->absenceType->name ?? __('Absences')];
            });

        $rows = $holidayRows->concat($absenceRows)->sortBy(function ($row) {
            return $row['user']->getFullName();
        })->values();

        $html = '<div class="dash-empty-inline margin-bottom">'.$rows->count().' '.e(__('out today')).'</div>';

        if ($rows->isEmpty()) {
            return $html.'<div class="dash-empty-inline">'.e(__('No data yet.')).'</div>';
        }

        $limit = ['small' => 3, 'medium' => 6, 'large' => 12][$size] ?? 6;

        $html .= '<div class="dash-list">';
        foreach ($rows->take($limit) as $row) {
            $html .= '<div class="dash-list-item">'
                .'<span class="dash-list-item-label">'.e($row['user']->getFullName()).'</span>'
                .'<span class="dash-list-item-value">'.e($row['label']).'</span>'
                .'</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
