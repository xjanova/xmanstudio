<?php

namespace App\Support;

use App\Models\Setting;

/**
 * One place that decides whether Cloudflare Turnstile guards a given form.
 *
 * The decision used to live in three copies — the middleware, the
 * <x-turnstile> component and the admin settings page — each calling
 * Setting::getValue() with its own default. They drifted, and the drift was
 * invisible: see sectionEnabled() below.
 *
 * The middleware and the component MUST agree. If the middleware demands a
 * token the component did not render, the form cannot be submitted by anyone.
 */
class Turnstile
{
    /**
     * Sections a form can be filed under.
     *
     * The string is the suffix of the setting key (turnstile_<section>) and the
     * argument passed to the route middleware ('turnstile:contact'). Adding a
     * section here is not enough on its own — it also needs a row on the admin
     * page so an operator can turn it off again.
     */
    public const SECTIONS = ['login', 'register', 'password', 'checkout', 'support', 'contact'];

    public static function siteKey(): string
    {
        return trim((string) Setting::getValue('turnstile_site_key', ''));
    }

    public static function secretKey(): string
    {
        return trim((string) Setting::getValue('turnstile_secret_key', ''));
    }

    /**
     * The master switch plus a usable key pair.
     *
     * Nothing is ever enforced without all three — a half-configured install
     * must not lock visitors out of every form on the site.
     */
    public static function configured(): bool
    {
        return (bool) Setting::getValue('turnstile_enabled', false)
            && static::siteKey() !== ''
            && static::secretKey() !== '';
    }

    /**
     * The stored per-section toggle, as the admin page shows it.
     *
     * Defaults to ON when the row is absent, and that default is the whole
     * point of this class. A missing row does not mean an operator chose to
     * leave the form unprotected — it means the section was added to the code
     * after the settings page was last saved, because the page only writes
     * rows when somebody presses Save.
     *
     * That is exactly what happened to the contact form: every other section
     * was written on 2026-03-08, the contact toggle shipped later, and the
     * absent row read as false in both the middleware and the component. The
     * result was silent on both sides — POSTs were waved straight through and
     * the widget was never drawn, so the page looked no different.
     */
    public static function sectionEnabled(string $section): bool
    {
        if ($section === '') {
            return true;
        }

        return (bool) Setting::getValue("turnstile_{$section}", true);
    }

    /**
     * Whether a request to this section must carry a solved token.
     */
    public static function enabledFor(string $section): bool
    {
        return static::configured() && static::sectionEnabled($section);
    }
}
