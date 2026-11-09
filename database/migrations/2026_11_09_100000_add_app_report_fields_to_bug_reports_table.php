<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "แจ้งปัญหา" from the desktop apps (CluadeX first) — POST /api/v1/product/{slug}/bug-reports.
     *
     * - ticket: what the user is shown and quotes back to the team ("BR-261109-K7Q4M").
     * - channel: 'app' for these reports. The old public GET /api/v1/bug-reports list skips them, so
     *   a user's logs and system details are never readable by anyone but an admin.
     * - client_report_id: the app's own id for one report. A retry from the app's offline queue, or a
     *   double click, sends the same id and gets the same ticket back instead of a second row.
     * - product_id / license_key_id: which app and which licence (and through it, which account).
     * - log_excerpt: the recent debug log the user agreed to send (already scrubbed by the app).
     *
     * Each column is checked on its own so a half-applied run can be applied again.
     */
    public function up(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('bug_reports', 'ticket')) {
                $table->string('ticket', 24)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('bug_reports', 'channel')) {
                $table->string('channel', 20)->nullable()->index()->after('ticket');
            }
            if (! Schema::hasColumn('bug_reports', 'client_report_id')) {
                $table->string('client_report_id', 64)->nullable()->after('channel');
            }
            if (! Schema::hasColumn('bug_reports', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('product_name')
                    ->constrained('products')->nullOnDelete();
            }
            if (! Schema::hasColumn('bug_reports', 'license_key_id')) {
                $table->foreignId('license_key_id')->nullable()->after('user_id')
                    ->constrained('license_keys')->nullOnDelete();
            }
            if (! Schema::hasColumn('bug_reports', 'log_excerpt')) {
                $table->longText('log_excerpt')->nullable()->after('stack_trace');
            }
        });

        if (! $this->hasIndex('bug_reports', 'bug_reports_product_client_unique')) {
            Schema::table('bug_reports', function (Blueprint $table) {
                $table->unique(['product_id', 'client_report_id'], 'bug_reports_product_client_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('bug_reports', 'bug_reports_product_client_unique')) {
            Schema::table('bug_reports', function (Blueprint $table) {
                $table->dropUnique('bug_reports_product_client_unique');
            });
        }

        Schema::table('bug_reports', function (Blueprint $table) {
            if (Schema::hasColumn('bug_reports', 'license_key_id')) {
                $table->dropConstrainedForeignId('license_key_id');
            }
            if (Schema::hasColumn('bug_reports', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
        });

        Schema::table('bug_reports', function (Blueprint $table) {
            foreach (['log_excerpt', 'client_report_id', 'channel', 'ticket'] as $column) {
                if (Schema::hasColumn('bug_reports', $column)) {
                    if ($column === 'ticket') {
                        $table->dropUnique('bug_reports_ticket_unique');
                    }
                    if ($column === 'channel') {
                        $table->dropIndex('bug_reports_channel_index');
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $i) => $i['name'] === $index);
    }
};
