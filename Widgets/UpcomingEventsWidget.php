<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Dashboard\Support\ModuleCheck;

/** Copies Calendar's own dashboard-widget query (Calendar/Support/
 * DashboardWidget.php) rather than calling that class directly — that
 * one returns core dashboard's pre-wrapped .dash-card HTML, styled for
 * core's page, not this module's own widget shell. */
class UpcomingEventsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return ModuleCheck::calendar();
    }

    public static function render(User $user, string $size): string
    {
        $limit = ['small' => 2, 'medium' => 5, 'large' => 8][$size] ?? 5;

        $ownCalendarIds = \Modules\Calendar\Entities\UserCalendar::where('user_id', $user->id)->pluck('id');

        $events = \Modules\Calendar\Entities\CalendarEvent::whereIn('calendar_id', $ownCalendarIds)
            ->where('end_at', '>=', now())
            ->orderBy('start_at')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            return '<div class="dash-empty-inline">'.e(__('No upcoming events.')).'</div>';
        }

        $html = '<div class="dash-list">';
        foreach ($events as $event) {
            $when = $event->all_day ? $event->start_at->format('d.m.') : $event->start_at->format('d.m. H:i');
            $html .= '<a class="dash-list-item" href="'.route('calendar.index').'#event-'.$event->id.'">'
                .'<span class="dash-list-item-label">'.e($event->title).'</span>'
                .'<span class="dash-list-item-value">'.e($when).'</span>'
                .'</a>';
        }
        $html .= '</div>';

        return $html;
    }
}
