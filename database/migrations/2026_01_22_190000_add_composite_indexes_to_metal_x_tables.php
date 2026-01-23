<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite indexes for common query patterns to improve performance.
     */
    public function up(): void
    {
        // Helper function to check if index exists (database-agnostic)
        $indexExists = function (string $table, string $indexName): bool {
            try {
                $connection = DB::connection()->getDriverName();

                if ($connection === 'mysql') {
                    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

                    return count($indexes) > 0;
                } elseif ($connection === 'sqlite') {
                    // For SQLite, check pragma index_list
                    $indexes = DB::select("PRAGMA index_list({$table})");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            return true;
                        }
                    }

                    return false;
                } else {
                    // For other databases, try to create and catch exception
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        };

        // Metal X Videos Indexes
        if (Schema::hasTable('metal_x_videos')) {
            Schema::table('metal_x_videos', function (Blueprint $table) use ($indexExists) {
                // Index for filtering by status and ordering by date
                if (Schema::hasColumn('metal_x_videos', 'is_active') &&
                    Schema::hasColumn('metal_x_videos', 'published_at') &&
                    ! $indexExists('metal_x_videos', 'idx_videos_active_published')) {
                    $table->index(['is_active', 'published_at'], 'idx_videos_active_published');
                }

                // Index for filtering by featured and ordering
                if (Schema::hasColumn('metal_x_videos', 'is_featured') &&
                    Schema::hasColumn('metal_x_videos', 'view_count') &&
                    ! $indexExists('metal_x_videos', 'idx_videos_featured_views')) {
                    $table->index(['is_featured', 'view_count'], 'idx_videos_featured_views');
                }

                // Index for AI metadata approval workflow
                if (Schema::hasColumn('metal_x_videos', 'ai_generated') &&
                    Schema::hasColumn('metal_x_videos', 'ai_approved') &&
                    Schema::hasColumn('metal_x_videos', 'updated_at') &&
                    ! $indexExists('metal_x_videos', 'idx_videos_ai_workflow')) {
                    $table->index(['ai_generated', 'ai_approved', 'updated_at'], 'idx_videos_ai_workflow');
                }

                // Index for playlist videos - only if columns exist
                if (Schema::hasColumn('metal_x_videos', 'playlist_id') &&
                    Schema::hasColumn('metal_x_videos', 'position') &&
                    ! $indexExists('metal_x_videos', 'idx_videos_playlist_position')) {
                    $table->index(['playlist_id', 'position'], 'idx_videos_playlist_position');
                }
            });
        }

        // Metal X Comments Indexes
        if (Schema::hasTable('metal_x_comments')) {
            Schema::table('metal_x_comments', function (Blueprint $table) use ($indexExists) {
                // Index for filtering comments by video and sorting
                if (Schema::hasColumn('metal_x_comments', 'video_id') &&
                    Schema::hasColumn('metal_x_comments', 'published_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_video_published')) {
                    $table->index(['video_id', 'published_at'], 'idx_comments_video_published');
                }

                // Index for AI reply workflow
                if (Schema::hasColumn('metal_x_comments', 'ai_replied') &&
                    Schema::hasColumn('metal_x_comments', 'is_spam') &&
                    Schema::hasColumn('metal_x_comments', 'deleted_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_ai_workflow')) {
                    $table->index(['ai_replied', 'is_spam', 'deleted_at'], 'idx_comments_ai_workflow');
                }

                // Index for sentiment analysis filtering
                if (Schema::hasColumn('metal_x_comments', 'sentiment') &&
                    Schema::hasColumn('metal_x_comments', 'sentiment_score') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_sentiment')) {
                    $table->index(['sentiment', 'sentiment_score'], 'idx_comments_sentiment');
                }

                // Index for moderation workflow
                if (Schema::hasColumn('metal_x_comments', 'requires_attention') &&
                    Schema::hasColumn('metal_x_comments', 'is_spam') &&
                    Schema::hasColumn('metal_x_comments', 'deleted_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_moderation')) {
                    $table->index(['requires_attention', 'is_spam', 'deleted_at'], 'idx_comments_moderation');
                }

                // Index for finding comments from blacklisted authors
                if (Schema::hasColumn('metal_x_comments', 'author_channel_id') &&
                    Schema::hasColumn('metal_x_comments', 'deleted_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_author_deleted')) {
                    $table->index(['author_channel_id', 'deleted_at'], 'idx_comments_author_deleted');
                }

                // Index for violation tracking
                if (Schema::hasColumn('metal_x_comments', 'violation_type') &&
                    Schema::hasColumn('metal_x_comments', 'created_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_violations')) {
                    $table->index(['violation_type', 'created_at'], 'idx_comments_violations');
                }

                // Index for blacklisted authors
                if (Schema::hasColumn('metal_x_comments', 'is_blacklisted_author') &&
                    Schema::hasColumn('metal_x_comments', 'created_at') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_blacklisted')) {
                    $table->index(['is_blacklisted_author', 'created_at'], 'idx_comments_blacklisted');
                }

                // Index for engagement metrics
                if (Schema::hasColumn('metal_x_comments', 'liked_by_channel') &&
                    Schema::hasColumn('metal_x_comments', 'ai_replied') &&
                    ! $indexExists('metal_x_comments', 'idx_comments_engagement')) {
                    $table->index(['liked_by_channel', 'ai_replied'], 'idx_comments_engagement');
                }
            });
        }

        // Metal X Blacklist Indexes
        if (Schema::hasTable('metal_x_blacklist')) {
            Schema::table('metal_x_blacklist', function (Blueprint $table) use ($indexExists) {
                // Index for checking if channel is blocked
                if (Schema::hasColumn('metal_x_blacklist', 'channel_id') &&
                    Schema::hasColumn('metal_x_blacklist', 'is_blocked') &&
                    ! $indexExists('metal_x_blacklist', 'idx_blacklist_channel_blocked')) {
                    $table->index(['channel_id', 'is_blocked'], 'idx_blacklist_channel_blocked');
                }

                // Index for violation tracking
                if (Schema::hasColumn('metal_x_blacklist', 'reason') &&
                    Schema::hasColumn('metal_x_blacklist', 'created_at') &&
                    ! $indexExists('metal_x_blacklist', 'idx_blacklist_reason_created')) {
                    $table->index(['reason', 'created_at'], 'idx_blacklist_reason_created');
                }

                // Index for finding recent violations
                if (Schema::hasColumn('metal_x_blacklist', 'last_violation_at') &&
                    Schema::hasColumn('metal_x_blacklist', 'is_blocked') &&
                    ! $indexExists('metal_x_blacklist', 'idx_blacklist_last_violation')) {
                    $table->index(['last_violation_at', 'is_blocked'], 'idx_blacklist_last_violation');
                }

                // Index for admin tracking
                if (Schema::hasColumn('metal_x_blacklist', 'blocked_by') &&
                    Schema::hasColumn('metal_x_blacklist', 'created_at') &&
                    ! $indexExists('metal_x_blacklist', 'idx_blacklist_blocked_by')) {
                    $table->index(['blocked_by', 'created_at'], 'idx_blacklist_blocked_by');
                }
            });
        }

        // Metal X Playlists Indexes
        if (Schema::hasTable('metal_x_playlists')) {
            Schema::table('metal_x_playlists', function (Blueprint $table) use ($indexExists) {
                // Index for active playlists
                if (Schema::hasColumn('metal_x_playlists', 'is_active') &&
                    Schema::hasColumn('metal_x_playlists', 'created_at') &&
                    ! $indexExists('metal_x_playlists', 'idx_playlists_active_created')) {
                    $table->index(['is_active', 'created_at'], 'idx_playlists_active_created');
                }
            });
        }

        // Settings Indexes
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) use ($indexExists) {
                // Index for finding settings by group
                if (Schema::hasColumn('settings', 'group') &&
                    Schema::hasColumn('settings', 'key') &&
                    ! $indexExists('settings', 'idx_settings_group_key')) {
                    $table->index(['group', 'key'], 'idx_settings_group_key');
                }

                // Index for public settings
                if (Schema::hasColumn('settings', 'is_public') &&
                    Schema::hasColumn('settings', 'group') &&
                    ! $indexExists('settings', 'idx_settings_public_group')) {
                    $table->index(['is_public', 'group'], 'idx_settings_public_group');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper to safely drop index (database-agnostic)
        $dropIndexIfExists = function (string $table, string $indexName): void {
            try {
                $connection = DB::connection()->getDriverName();
                $exists = false;

                if ($connection === 'mysql') {
                    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                    $exists = count($indexes) > 0;
                } elseif ($connection === 'sqlite') {
                    $indexes = DB::select("PRAGMA index_list({$table})");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            $exists = true;
                            break;
                        }
                    }
                }

                if ($exists) {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                        $tableBlueprint->dropIndex($indexName);
                    });
                }
            } catch (\Exception $e) {
                // Index doesn't exist or table doesn't exist, ignore
            }
        };

        if (Schema::hasTable('metal_x_videos')) {
            $dropIndexIfExists('metal_x_videos', 'idx_videos_active_published');
            $dropIndexIfExists('metal_x_videos', 'idx_videos_featured_views');
            $dropIndexIfExists('metal_x_videos', 'idx_videos_ai_workflow');
            $dropIndexIfExists('metal_x_videos', 'idx_videos_playlist_position');
        }

        if (Schema::hasTable('metal_x_comments')) {
            $dropIndexIfExists('metal_x_comments', 'idx_comments_video_published');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_ai_workflow');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_sentiment');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_moderation');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_author_deleted');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_violations');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_blacklisted');
            $dropIndexIfExists('metal_x_comments', 'idx_comments_engagement');
        }

        if (Schema::hasTable('metal_x_blacklist')) {
            $dropIndexIfExists('metal_x_blacklist', 'idx_blacklist_channel_blocked');
            $dropIndexIfExists('metal_x_blacklist', 'idx_blacklist_reason_created');
            $dropIndexIfExists('metal_x_blacklist', 'idx_blacklist_last_violation');
            $dropIndexIfExists('metal_x_blacklist', 'idx_blacklist_blocked_by');
        }

        if (Schema::hasTable('metal_x_playlists')) {
            $dropIndexIfExists('metal_x_playlists', 'idx_playlists_active_created');
        }

        if (Schema::hasTable('settings')) {
            $dropIndexIfExists('settings', 'idx_settings_group_key');
            $dropIndexIfExists('settings', 'idx_settings_public_group');
        }
    }
};
