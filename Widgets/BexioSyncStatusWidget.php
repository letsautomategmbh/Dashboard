<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Bexio\Support\BexioAuth;
use Modules\Dashboard\Support\ModuleCheck;

/** "Bexio-Sync-Status" — admin-only, four Option-backed reads, no query. */
class BexioSyncStatusWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return $user->isAdmin() && ModuleCheck::bexio();
    }

    public static function render(User $user, string $size): string
    {
        if (!BexioAuth::isConnected()) {
            $html = '<div class="dash-badge dash-badge-muted">'.e(__('Not connected')).'</div>';
            $html .= '<div class="margin-top"><a class="dash-widget-link" href="'.route('bexio.settings.index').'">'.e(__('Bexio')).' &rarr;</a></div>';

            return $html;
        }

        $lastSynced = BexioAuth::lastSyncedAt();
        $lastError = BexioAuth::lastSyncError();

        $html = '<div class="dash-badge '.($lastError ? 'dash-badge-danger' : 'dash-badge-success').'">'.e($lastError ? __('Sync error') : __('Connected')).'</div>';
        $html .= '<div class="dash-empty-inline margin-top">'.e(__('Last synced')).': '.($lastSynced ? e(\Carbon\Carbon::parse($lastSynced)->format('d.m.Y H:i')) : e(__('never'))).'</div>';

        if ($size !== 'small' && $lastError) {
            $html .= '<div class="dash-empty-inline">'.e($lastError).'</div>';
        }

        $html .= '<div class="margin-top"><a class="dash-widget-link" href="'.route('bexio.settings.index').'">'.e(__('Bexio')).' &rarr;</a></div>';

        return $html;
    }
}
