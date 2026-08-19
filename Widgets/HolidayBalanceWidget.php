<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Hr\Support\HolidayBalance;

/** "Ferien-/Urlaubssaldo" — single static call into Hr's own, fully-built
 * balance calculator (allowance + rollover - used, no floor at 0). Only
 * available when the Hr module is installed. */
class HolidayBalanceWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Hr\Support\HolidayBalance');
    }

    public static function render(User $user, string $size): string
    {
        $year = now()->year;
        $balance = HolidayBalance::balanceForYear($user->id, $year);

        $html = '<div class="dash-stat-row"><div class="dash-stat">'
            .'<span class="dash-stat-value">'.number_format($balance, 1).' h</span>'
            .'<span class="dash-stat-label">'.e(__('Balance')).' '.$year.'</span>'
            .'</div></div>';

        if ($size !== 'small') {
            $allowance = HolidayBalance::allowanceForYear($user->id, $year);
            $used = HolidayBalance::usedForYear($user->id, $year);
            $html .= '<div class="dash-empty-inline">'.e(__('Budgeted')).': '.number_format($allowance, 1).' h &middot; '.e(__('Spent')).': '.number_format($used, 1).' h</div>';
        }

        if ($size === 'large') {
            // Balances can legitimately be negative (no floor at 0, per
            // HolidayBalance's own doc comment) — a plain list reads more
            // honestly here than a proportional bar would for a value
            // that isn't a share of some whole.
            $html .= '<div class="dash-list margin-top">';
            for ($i = 2; $i >= 1; $i--) {
                $y = $year - $i;
                $b = HolidayBalance::balanceForYear($user->id, $y);
                $html .= '<div class="dash-list-item"><span class="dash-list-item-label">'.$y.'</span><span class="dash-list-item-value">'.number_format($b, 1).' h</span></div>';
            }
            $html .= '</div>';
        }

        return $html;
    }
}
