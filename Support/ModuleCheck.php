<?php

namespace Modules\Dashboard\Support;

use Illuminate\Support\Facades\Route;

/** Whether a sibling module is genuinely active right now — not just
 * "installed". A deactivated module's PHP classes stay perfectly
 * class_exists()-able (Composer's classmap autoloading doesn't care about
 * FreeScout's own active/inactive DB flag), so class_exists() alone is
 * NOT a reliable "is this feature actually available" check — confirmed
 * empirically: flipping a module inactive in the `modules` table and
 * re-bootstrapping still leaves class_exists() on its entities returning
 * true. A route only exists if that module's ServiceProvider actually
 * booted, which only happens for modules FreeScout currently considers
 * active, so Route::has() on one of its own stable routes is the signal
 * every widget's isAvailable() should check instead. */
class ModuleCheck
{
    public static function calendar()
    {
        return Route::has('calendar.index');
    }

    public static function invoicing()
    {
        return Route::has('invoicing.time_entries.index');
    }

    public static function hr()
    {
        return Route::has('hr.holiday_requests.pending');
    }

    public static function notes()
    {
        return Route::has('notes.index');
    }

    public static function bexio()
    {
        return Route::has('bexio.settings.index');
    }
}
