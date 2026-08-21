<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Hr\Entities\AbsenceRequest;
use Modules\Hr\Entities\HolidayRequest;

/** "Offene Urlaubs- und Absenzanträge" — admin-only, the combined
 * still-pending queue from both HolidayRequest (Urlaub) and AbsenceRequest
 * (Militär, Krankheit, …) — the two separate admin approval pages
 * (hr.holiday_requests.pending / hr.absence_requests.pending) merged into
 * one glanceable list. */
class LeaveRequestsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && class_exists('Modules\Hr\Entities\HolidayRequest');
    }

    public static function render(User $user, string $size): string
    {
        $holidayRows = HolidayRequest::where('status', HolidayRequest::STATUS_PENDING)
            ->with('user')
            ->get()
            ->map(function ($row) {
                return ['user' => $row->user, 'label' => __('Holiday Requests'), 'date' => $row->start_date, 'url' => route('hr.holiday_requests.pending')];
            });

        $absenceRows = AbsenceRequest::where('status', AbsenceRequest::STATUS_PENDING)
            ->with(['user', 'absenceType'])
            ->get()
            ->map(function ($row) {
                return ['user' => $row->user, 'label' => $row->absenceType->name ?? __('Absences'), 'date' => $row->start_date, 'url' => route('hr.absence_requests.pending')];
            });

        $rows = $holidayRows->concat($absenceRows)->sortBy('date')->values();

        $html = '<div class="dash-empty-inline margin-bottom">'.$rows->count().' '.e(__('open')).'</div>';

        if ($rows->isEmpty()) {
            return $html.'<div class="dash-empty-inline">'.e(__('No pending requests.')).'</div>';
        }

        $limit = ['small' => 2, 'medium' => 4, 'large' => 8][$size] ?? 4;

        $html .= '<div class="dash-list">';
        foreach ($rows->take($limit) as $row) {
            $html .= '<a class="dash-list-item" href="'.$row['url'].'">'
                .'<span class="dash-list-item-label">'.e($row['user']->getFullName()).' &middot; '.e($row['label']).'</span>'
                .'<span class="dash-list-item-value">'.e($row['date']->format('d.m.')).'</span>'
                .'</a>';
        }
        $html .= '</div>';
        $html .= '<a class="dash-widget-link" href="'.route('hr.holiday_requests.pending').'">'.e(__('Holiday Requests')).' &rarr;</a> '
            .'<a class="dash-widget-link" href="'.route('hr.absence_requests.pending').'">'.e(__('Absence Requests')).' &rarr;</a>';

        return $html;
    }
}
