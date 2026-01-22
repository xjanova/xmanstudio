<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('metal_x_videos', function (Blueprint $table) {
            // Index for filtering by status and ordering by date
            $table->index(['is_active', 'published_at'], 'idx_videos_active_published');

            // Index for filtering by featured and ordering
            $table->index(['is_featured', 'view_count'], 'idx_videos_featured_views');

            // Index for AI metadata approval workflow
            $table->index(['ai_generated', 'ai_approved', 'updated_at'], 'idx_videos_ai_workflow');

            // Index for playlist videos
            $table->index(['playlist_id', 'position'], 'idx_videos_playlist_position');
        });

        Schema::table('metal_x_comments', function (Blueprint $table) {
            // Index for filtering comments by video and sorting
            $table->index(['video_id', 'published_at'], 'idx_comments_video_published');

            // Index for AI reply workflow
            $table->index(['ai_replied', 'is_spam', 'deleted_at'], 'idx_comments_ai_workflow');

            // Index for sentiment analysis filtering
            $table->index(['sentiment', 'sentiment_score'], 'idx_comments_sentiment');

            // Index for moderation workflow
            $table->index(['requires_attention', 'is_spam', 'deleted_at'], 'idx_comments_moderation');

            // Index for finding comments from blacklisted authors
            $table->index(['author_channel_id', 'deleted_at'], 'idx_comments_author_deleted');

            // Index for violation tracking
            $table->index(['violation_type', 'created_at'], 'idx_comments_violations');

            // Index for blacklisted authors
            $table->index(['is_blacklisted_author', 'created_at'], 'idx_comments_blacklisted');

            // Index for engagement metrics
            $table->index(['liked_by_channel', 'ai_replied'], 'idx_comments_engagement');
        });

        Schema::table('metal_x_blacklist', function (Blueprint $table) {
            // Index for checking if channel is blocked
            $table->index(['channel_id', 'is_blocked'], 'idx_blacklist_channel_blocked');

            // Index for violation tracking
            $table->index(['reason', 'created_at'], 'idx_blacklist_reason_created');

            // Index for finding recent violations
            $table->index(['last_violation_at', 'is_blocked'], 'idx_blacklist_last_violation');

            // Index for admin tracking
            $table->index(['blocked_by', 'created_at'], 'idx_blacklist_blocked_by');
        });

        Schema::table('metal_x_playlists', function (Blueprint $table) {
            // Index for active playlists
            $table->index(['is_active', 'created_at'], 'idx_playlists_active_created');
        });

        Schema::table('settings', function (Blueprint $table) {
            // Index for finding settings by group
            $table->index(['group', 'key'], 'idx_settings_group_key');

            // Index for public settings
            $table->index(['is_public', 'group'], 'idx_settings_public_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->dropIndex('idx_videos_active_published');
            $table->dropIndex('idx_videos_featured_views');
            $table->dropIndex('idx_videos_ai_workflow');
            $table->dropIndex('idx_videos_playlist_position');
        });

        Schema::table('metal_x_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_video_published');
            $table->dropIndex('idx_comments_ai_workflow');
            $table->dropIndex('idx_comments_sentiment');
            $table->dropIndex('idx_comments_moderation');
            $table->dropIndex('idx_comments_author_deleted');
            $table->dropIndex('idx_comments_violations');
            $table->dropIndex('idx_comments_blacklisted');
            $table->dropIndex('idx_comments_engagement');
        });

        Schema::table('metal_x_blacklist', function (Blueprint $table) {
            $table->dropIndex('idx_blacklist_channel_blocked');
            $table->dropIndex('idx_blacklist_reason_created');
            $table->dropIndex('idx_blacklist_last_violation');
            $table->dropIndex('idx_blacklist_blocked_by');
        });

        Schema::table('metal_x_playlists', function (Blueprint $table) {
            $table->dropIndex('idx_playlists_active_created');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_group_key');
            $table->dropIndex('idx_settings_public_group');
        });
    }
};
