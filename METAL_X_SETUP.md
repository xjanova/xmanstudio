# Metal-X Project Music Channel - Setup Guide

## Overview
This feature adds a dedicated section for the Metal-X Project music channel, allowing you to showcase:
- YouTube channel and videos
- Team members with photos and social links
- Beautiful presentation on a dedicated page
- Integration with the main website homepage

## Files Created/Modified

### 1. Database Migration
- **File:** `database/migrations/2026_01_07_150602_create_metal_x_team_members_table.php`
- **Purpose:** Creates the `metal_x_team_members` table to store team member information

### 2. Model
- **File:** `app/Models/MetalXTeamMember.php`
- **Purpose:** Eloquent model for managing Metal-X team members

### 3. Controllers
- **File:** `app/Http/Controllers/MetalXController.php`
  - Public-facing controller for the Metal-X page

- **File:** `app/Http/Controllers/Admin/MetalXTeamController.php`
  - Admin controller for managing team members (CRUD operations)

- **File:** `app/Http/Controllers/Admin/MetalXSettingsController.php`
  - Admin controller for managing YouTube channel settings

### 4. Views
- **File:** `resources/views/metal-x/index.blade.php`
  - Beautiful public page showcasing the Metal-X Project
  - Displays team members, YouTube channel, and videos
  - Automatically loads YouTube videos if API key is configured

### 5. Routes
- **File:** `routes/web.php`
  - Added public route: `/metal-x`
  - Added admin routes: `/admin/metal-x/*`

### 6. Homepage Integration
- **File:** `resources/views/home.blade.php`
  - Added a beautiful Metal-X section linking to the dedicated page

## Installation Steps

### Step 1: Run Database Migration
```bash
php artisan migrate
```

This will create the `metal_x_team_members` table with the following columns:
- `id` - Primary key
- `name` - Team member name (English)
- `name_th` - Team member name (Thai)
- `role` - Role/Position (English)
- `role_th` - Role/Position (Thai)
- `bio` - Biography (English)
- `bio_th` - Biography (Thai)
- `image` - Profile image path
- `youtube_url` - YouTube channel/video URL
- `facebook_url` - Facebook profile URL
- `instagram_url` - Instagram profile URL
- `twitter_url` - Twitter/X profile URL
- `tiktok_url` - TikTok profile URL
- `order` - Display order
- `is_active` - Active status
- `timestamps` - Created/Updated timestamps

### Step 2: Configure YouTube Channel Settings

Navigate to the admin panel:
1. Go to `/admin/metal-x/settings`
2. Fill in the following information:
   - **Channel Name:** Metal-X Project
   - **Channel Description:** Your channel description
   - **Channel URL:** https://www.youtube.com/@Metal-XProject
   - **Channel Logo:** Upload your channel logo image
   - **Channel Banner:** Upload your channel banner image
   - **YouTube API Key:** (Optional) Your YouTube Data API v3 key

#### Getting a YouTube API Key (Optional but Recommended):
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable "YouTube Data API v3"
4. Create credentials (API Key)
5. Restrict the API key to "YouTube Data API v3" for security
6. Copy and paste the API key in the admin settings

**Note:** Without the API key, videos won't load automatically, but you can still link to your channel.

### Step 3: Add Team Members

Navigate to the admin panel:
1. Go to `/admin/metal-x`
2. Click "Add New Team Member"
3. Fill in the team member information:
   - Name (English and Thai)
   - Role/Position (English and Thai)
   - Biography (English and Thai)
   - Upload profile image
   - Add social media links
   - Set display order (lower numbers appear first)
   - Set active status

4. Click "Save"

Repeat for all team members.

### Step 4: Access the Metal-X Page

Public page is now available at:
- **URL:** `/metal-x`
- **Direct link:** https://yoursite.com/metal-x

The homepage also features a beautiful section linking to the Metal-X page.

## Admin Panel Features

### Team Management (`/admin/metal-x`)
- ✅ View all team members
- ✅ Add new team members
- ✅ Edit existing team members
- ✅ Delete team members
- ✅ Search and filter team members
- ✅ Activate/deactivate team members
- ✅ Reorder team members

### Settings Management (`/admin/metal-x/settings`)
- ✅ Configure YouTube channel information
- ✅ Upload channel logo and banner
- ✅ Set YouTube API key for automatic video loading
- ✅ Update channel description

## Features

### Public Page Features
1. **Hero Section**
   - Channel banner background
   - Channel logo display
   - Channel name and description
   - Subscribe button linking to YouTube

2. **Latest Videos Section**
   - Automatically loads latest 6 videos from YouTube (if API key is configured)
   - Beautiful grid layout
   - Video thumbnails with play overlay
   - Click to watch on YouTube

3. **Team Section**
   - Beautiful grid layout of team members
   - Profile photos
   - Names and roles (bilingual support)
   - Biographies
   - Social media links (YouTube, Facebook, Instagram, Twitter, TikTok)
   - Hover effects

4. **Call to Action**
   - Subscribe button
   - Back to main site button

### Homepage Integration
- Beautiful gradient section
- Two cards: YouTube Channel and Team & Portfolio
- Links to Metal-X page and YouTube channel
- Fully responsive design

## Design Features
- ✨ Beautiful gradient backgrounds (purple, pink, red)
- ✨ Modern card designs with hover effects
- ✨ Fully responsive (mobile, tablet, desktop)
- ✨ Dark mode support
- ✨ Smooth animations and transitions
- ✨ Professional YouTube integration
- ✨ Social media icons and links
- ✨ Bilingual support (English/Thai)

## Database Structure

### metal_x_team_members table
```sql
CREATE TABLE metal_x_team_members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_th VARCHAR(255),
    role VARCHAR(255) NOT NULL,
    role_th VARCHAR(255),
    bio TEXT,
    bio_th TEXT,
    image VARCHAR(255),
    youtube_url VARCHAR(255),
    facebook_url VARCHAR(255),
    instagram_url VARCHAR(255),
    twitter_url VARCHAR(255),
    tiktok_url VARCHAR(255),
    `order` INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Settings (stored in settings table)
- `metalx_channel_name` - Channel name
- `metalx_channel_description` - Channel description
- `metalx_channel_url` - YouTube channel URL
- `metalx_channel_logo` - Logo image path
- `metalx_channel_banner` - Banner image path
- `youtube_api_key` - YouTube API key

## Troubleshooting

### Videos not loading
- Ensure you have configured the YouTube API key in `/admin/metal-x/settings`
- Verify the API key is valid and has YouTube Data API v3 enabled
- Check browser console for any JavaScript errors

### Images not displaying
- Ensure you have run `php artisan storage:link` to create the symbolic link
- Check that images are uploaded to the `storage/app/public/metal-x` directory
- Verify file permissions

### Team members not showing
- Ensure team members are marked as "Active" in the admin panel
- Check the database to verify team members exist
- Clear Laravel cache: `php artisan cache:clear`

## Support

For any issues or questions about this feature, please contact the development team.

## Credits

Developed by XMAN Studio
Feature: Metal-X Project Music Channel Integration
