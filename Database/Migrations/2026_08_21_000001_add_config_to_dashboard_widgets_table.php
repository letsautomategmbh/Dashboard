<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-widget-instance settings (currently only the Weather widget's own
 * city) — a widget that needs no configuration just never touches this
 * column, so the fifteen already-shipped widgets are unaffected. */
class AddConfigToDashboardWidgetsTable extends Migration
{
    public function up()
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->text('config')->nullable()->after('size');
        });
    }

    public function down()
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
}
