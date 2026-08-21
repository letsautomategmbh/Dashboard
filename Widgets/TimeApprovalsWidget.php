<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Dashboard\Support\ModuleCheck;
use Modules\Invoicing\Entities\TimeEntryApproval;

/** "Offene Zeitgenehmigungen" — admin-only, every staff member's
 * still-pending weekly-report submissions, exact same query
 * TimeApprovalsController::pending() already lists on its own page. */
class TimeApprovalsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && ModuleCheck::invoicing();
    }

    public static function render(User $user, string $size): string
    {
        $query = TimeEntryApproval::where('status', TimeEntryApproval::STATUS_PENDING)->with('user');

        $count = (clone $query)->count();
        $listUrl = route('invoicing.time_approvals.pending');

        $html = '<div class="dash-empty-inline margin-bottom">'.$count.' '.e(__('open')).'</div>';

        $limit = ['small' => 2, 'medium' => 4, 'large' => 8][$size] ?? 4;
        $rows = (clone $query)->orderBy('submitted_at')->limit($limit)->get();

        if ($rows->isEmpty()) {
            return $html.'<div class="dash-empty-inline">'.e(__('No pending requests.')).'</div>'
                .'<a class="dash-widget-link" href="'.$listUrl.'">'.e(__('View all')).' &rarr;</a>';
        }

        $html .= '<div class="dash-list">';
        foreach ($rows as $row) {
            $weekLabel = 'KW'.$row->week_start->format('W');
            $html .= '<a class="dash-list-item" href="'.$listUrl.'">'
                .'<span class="dash-list-item-label">'.e($row->user->getFullName()).'</span>'
                .'<span class="dash-list-item-value">'.e($weekLabel).'</span>'
                .'</a>';
        }
        $html .= '</div>';
        $html .= '<a class="dash-widget-link" href="'.$listUrl.'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
