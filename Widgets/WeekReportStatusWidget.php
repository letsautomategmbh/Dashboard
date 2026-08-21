<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use Modules\Dashboard\Support\ModuleCheck;
use Modules\Invoicing\Entities\TimeEntry;
use Modules\Invoicing\Entities\TimeEntryApproval;

/** "Wochenrapport-Status" — same weekApproval query
 * TimeEntriesOverviewController::index() already runs; the "submit"
 * button posts to that module's own existing
 * invoicing.time_entries.overview_approval.store route, no new backend
 * endpoint needed here. */
class WeekReportStatusWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return ModuleCheck::invoicing();
    }

    public static function render(User $user, string $size): string
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $approval = TimeEntryApproval::where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        $statusKey = $approval ? $approval->status : '';
        $label = match ($statusKey) {
            TimeEntryApproval::STATUS_PENDING => __('Pending'),
            TimeEntryApproval::STATUS_APPROVED => __('Approved'),
            TimeEntryApproval::STATUS_REJECTED => __('Rejected'),
            default => __('Not submitted'),
        };
        $badgeClass = match ($statusKey) {
            TimeEntryApproval::STATUS_PENDING => 'dash-badge-warning',
            TimeEntryApproval::STATUS_APPROVED => 'dash-badge-success',
            TimeEntryApproval::STATUS_REJECTED => 'dash-badge-danger',
            default => 'dash-badge-muted',
        };

        $html = '<div class="dash-stat-row"><div class="dash-stat">'
            .'<span class="dash-badge '.$badgeClass.'">'.e($label).'</span>'
            .'<span class="dash-stat-label">'.e(__('Calendar Week')).' '.$weekStart->format('W').'</span>'
            .'</div></div>';

        if ($size !== 'small') {
            $weekHours = (float) TimeEntry::where('user_id', $user->id)
                ->whereBetween('entry_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->sum('hours');
            $html .= '<div class="dash-empty-inline">'.e(__('Captured Time')).': '.TimeEntry::formatHours($weekHours).'</div>';
        }

        $locksEntries = $approval && $approval->locksEntries();
        if (!$locksEntries) {
            $html .= '<form method="POST" action="'.route('invoicing.time_entries.overview_approval.store').'" class="dash-inline-form">'
                .csrf_field()
                .'<button type="submit" class="btn btn-xs btn-primary margin-top">'.e(__('Request Approval')).'</button>'
                .'</form>';
        }

        $html .= '<a class="dash-widget-link" href="'.route('invoicing.time_entries.overview').'">'.e(__('View your timesheet')).' &rarr;</a>';

        return $html;
    }
}
