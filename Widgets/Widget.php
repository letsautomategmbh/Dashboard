<?php

namespace Modules\Dashboard\Widgets;

use App\User;

/** Common contract every dashboard widget renderer implements — see
 * Support/WidgetRegistry.php for the key -> class map. isAvailable()
 * gates both whether an existing board row still renders (a widget whose
 * source module got deactivated after being added just silently
 * disappears, without deleting the row — it comes back if the module
 * does) and whether the widget is offered in the "add widget" picker. */
interface Widget
{
    public static function isAvailable(User $user): bool;

    /** Returns the widget's own inner HTML only — the view wraps it in
     * the .dash-widget/.dash-widget-{size} card shell. */
    public static function render(User $user, string $size): string;
}
