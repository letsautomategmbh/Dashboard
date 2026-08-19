<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Invoicing\Entities\Invoice;
use Modules\Invoicing\Entities\TimeEntry;

/** "Offene Abrechnungen/Finanzen" — admin-only (company-wide financials,
 * not personal data, same reasoning as bexio_sync_status). */
class OpenInvoicesWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && class_exists('Modules\Invoicing\Entities\Invoice');
    }

    public static function render(User $user, string $size): string
    {
        // ->sum('amount') on the query builder would try SUM(amount) in
        // SQL — amount is a PHP accessor (hours * product rate), not a
        // real column, exactly the distinction BillingController::
        // timeEntries() already has to respect. Load the (small) unbilled
        // set and sum the accessor in PHP instead.
        $unbilledAmount = (float) TimeEntry::with('product')->unbilledFromCompleted()->get()->sum('amount');
        $draftCount = Invoice::where('status', Invoice::STATUS_DRAFT)->count();

        $html = '<div class="dash-stat-row">'
            .'<div class="dash-stat"><span class="dash-stat-value">'.number_format($unbilledAmount, 0).'</span><span class="dash-stat-label">'.e(__('CHF unbilled')).'</span></div>';

        if ($size !== 'small') {
            $html .= '<div class="dash-stat"><span class="dash-stat-value">'.$draftCount.'</span><span class="dash-stat-label">'.e(__('Open Abrechnungen')).'</span></div>';
        }
        $html .= '</div>';

        if ($size === 'large') {
            $html .= '<a class="dash-widget-link" href="'.route('invoicing.finance.time_entries').'">'.e(__('Open Time Entries')).' &rarr;</a><br>';
        }
        $html .= '<a class="dash-widget-link" href="'.route('invoicing.finance.invoices').'">'.e(__('Abrechnungen')).' &rarr;</a>';

        return $html;
    }
}
