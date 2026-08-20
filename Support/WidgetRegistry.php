<?php

namespace Modules\Dashboard\Support;

use App\User;
use Modules\Dashboard\Widgets\BexioSyncStatusWidget;
use Modules\Dashboard\Widgets\HolidayBalanceWidget;
use Modules\Dashboard\Widgets\MyConversationsWidget;
use Modules\Dashboard\Widgets\MyWeeklyBillableHoursWidget;
use Modules\Dashboard\Widgets\NotesWidget;
use Modules\Dashboard\Widgets\OpenInvoicesWidget;
use Modules\Dashboard\Widgets\ProjectsWidget;
use Modules\Dashboard\Widgets\QuickActionsWidget;
use Modules\Dashboard\Widgets\StatisticsWidget;
use Modules\Dashboard\Widgets\TasksWidget;
use Modules\Dashboard\Widgets\TeamBillableHoursWidget;
use Modules\Dashboard\Widgets\TimetrackingWidget;
use Modules\Dashboard\Widgets\UpcomingEventsWidget;
use Modules\Dashboard\Widgets\WeekReportStatusWidget;

/** The widget catalog: key -> {label, icon, sizes, default_size, class,
 * admin_only}. Adding a 13th widget is a one-file addition (a new
 * Widgets/*.php implementing Widget) plus one entry here — nothing else
 * in the module needs to change. */
class WidgetRegistry
{
    public static $widgets = [
        'upcoming_events' => [
            'label' => 'Upcoming Events',
            'icon' => 'glyphicon-calendar',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => UpcomingEventsWidget::class,
            'admin_only' => false,
        ],
        'timetracking' => [
            'label' => 'Timetracking',
            'icon' => 'glyphicon-time',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => TimetrackingWidget::class,
            'admin_only' => false,
        ],
        'my_conversations' => [
            'label' => 'My open conversations',
            'icon' => 'glyphicon-envelope',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => MyConversationsWidget::class,
            'admin_only' => false,
        ],
        'projects' => [
            'label' => 'Projects',
            'icon' => 'glyphicon-briefcase',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'small',
            'class' => ProjectsWidget::class,
            'admin_only' => false,
        ],
        'tasks' => [
            'label' => 'Tasks',
            'icon' => 'glyphicon-check',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'small',
            'class' => TasksWidget::class,
            'admin_only' => false,
        ],
        'statistics' => [
            'label' => 'Statistics',
            'icon' => 'glyphicon-stats',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => StatisticsWidget::class,
            'admin_only' => false,
        ],
        'week_report_status' => [
            'label' => 'Week Report',
            'icon' => 'glyphicon-ok-circle',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'small',
            'class' => WeekReportStatusWidget::class,
            'admin_only' => false,
        ],
        'holiday_balance' => [
            'label' => 'Balance',
            'icon' => 'glyphicon-plane',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'small',
            'class' => HolidayBalanceWidget::class,
            'admin_only' => false,
        ],
        'notes' => [
            'label' => 'Notes',
            'icon' => 'glyphicon-list-alt',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'small',
            'class' => NotesWidget::class,
            'admin_only' => false,
        ],
        'quick_actions' => [
            'label' => 'Quick Actions',
            'icon' => 'glyphicon-flash',
            'sizes' => ['small', 'medium'],
            'default_size' => 'medium',
            'class' => QuickActionsWidget::class,
            'admin_only' => false,
        ],
        'open_invoices' => [
            'label' => 'Open Time Entries',
            'icon' => 'glyphicon-list-alt',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => OpenInvoicesWidget::class,
            'admin_only' => true,
        ],
        'bexio_sync_status' => [
            'label' => 'Bexio',
            'icon' => 'glyphicon-refresh',
            'sizes' => ['small', 'medium'],
            'default_size' => 'small',
            'class' => BexioSyncStatusWidget::class,
            'admin_only' => true,
        ],
        'team_billable_hours' => [
            'label' => 'Team',
            'icon' => 'glyphicon-user',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => TeamBillableHoursWidget::class,
            'admin_only' => true,
        ],
        'my_weekly_billable' => [
            'label' => 'Weekly Billable Hours',
            'icon' => 'glyphicon-signal',
            'sizes' => ['small', 'medium', 'large'],
            'default_size' => 'medium',
            'class' => MyWeeklyBillableHoursWidget::class,
            'admin_only' => false,
        ],
    ];

    /** The widget keys seeded onto a brand-new user's board the first
     * time they open the dashboard (see DashboardController::index()) —
     * the six originally-named widgets, at a sensible starting layout. */
    public static $defaultKeys = ['upcoming_events', 'timetracking', 'my_conversations', 'tasks', 'projects', 'statistics'];

    public static function get($key)
    {
        return self::$widgets[$key] ?? null;
    }

    public static function isAvailable($key, User $user)
    {
        $widget = self::get($key);
        if (!$widget) {
            return false;
        }

        if ($widget['admin_only'] && !$user->isAdmin()) {
            return false;
        }

        $class = $widget['class'];

        return class_exists($class) && $class::isAvailable($user);
    }

    /** Every registry key currently available to this user — used both
     * to filter an existing board (a row whose widget/module vanished
     * just stops rendering, without deleting the row) and to build the
     * "add widget" picker's offered list. */
    public static function availableKeys(User $user)
    {
        return array_values(array_filter(array_keys(self::$widgets), function ($key) use ($user) {
            return self::isAvailable($key, $user);
        }));
    }
}
