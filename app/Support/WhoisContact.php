<?php

namespace App\Support;

use App\Models\DomainContact;

/**
 * A registrant, in the shape the registrar's WHOIS endpoint accepts.
 *
 * The registrar documents `whois_details` as a bare object, and the keys this
 * app first sent (`address1`, `zip`, `state`, `phone`, `country`) were never
 * its keys: every first purchase would have been refused at the profile step.
 * The real rules live in config/domain_whois.php, read back from its 422s.
 *
 * Two jobs, kept side by side so they cannot drift apart:
 *   - details(): the payload, cleaned the way the registrar needs it
 *   - problems(): what it would still refuse, in Thai, field by field — so the
 *     form can say so before anyone pays, instead of a refund after
 */
final class WhoisContact
{
    public const ADDRESS_MAX = 50;

    public const CITY_MAX = 64;

    public const NAME_MIN = 2;

    public const NAME_MAX = 64;

    /** Refused anywhere in `address` (a slash is turned into a dash first). */
    private const ADDRESS_FORBIDDEN = '/[~!@#$%^&*`+_=()|\'"\[\]{}°º]/u';

    /** Refused in `city`: the address set plus < > . - */
    private const CITY_FORBIDDEN = '/[~<>`!.@#$%^&*+_=()|\'"\[\]{}°º\/\\\\-]/u';

    /** Thai titles people type in front of a first name. */
    private const TITLE = '/^(?:นางสาว|นาง|นาย|น\.ส\.|ด\.ช\.|ด\.ญ\.|mrs\.?|mr\.?|ms\.?|miss)\s+/iu';

    /**
     * Spellings of Thai provinces that the registrar's list and our Thai
     * labels do not cover once spaces are ignored.
     */
    private const TH_ALIASES = [
        'กรุงเทพ' => 'Bangkok',
        'กรุงเทพฯ' => 'Bangkok',
        'กทม' => 'Bangkok',
        'bangkokmetropolis' => 'Bangkok',
        'krungthep' => 'Bangkok',
        'krungthepmahanakhon' => 'Bangkok',
        'อยุธยา' => 'Phra Nakhon Si Ayutthaya',
        'ayutthaya' => 'Phra Nakhon Si Ayutthaya',
        'โคราช' => 'Nakhon Ratchasima',
        'korat' => 'Nakhon Ratchasima',
        'sukhothai' => 'Sukhothai Thani',
        'srisaket' => 'Sisaket',
        'srisaketh' => 'Sisaket',
        'samutprakarn' => 'Samut Prakan',
        'chainath' => 'Chai Nat',
        'nongbualamphu' => 'Nong Bua Lam Phu',
    ];

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function countries(): array
    {
        return (array) config('domain_whois.countries', []);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function country(?string $code): ?array
    {
        $countries = self::countries();

        return $countries[strtoupper((string) $code)] ?? null;
    }

    public static function supports(?string $code): bool
    {
        return self::country($code) !== null;
    }

    // ------------------------------------------------------------- cleaning

    /** "๑๒๓" → "123": the registrar's "at least one number" means 0-9. */
    public static function arabicDigits(?string $value): string
    {
        return strtr((string) $value, [
            '๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4',
            '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9',
        ]);
    }

    /**
     * Collapse spaces and drop a title typed in front ("นาย สมชาย"). Only a
     * title followed by a space: "นายิกา" is a name, not นาย + ิกา.
     */
    public static function name(?string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return trim((string) preg_replace(self::TITLE, '', $value));
    }

    /**
     * The one address line the registrar takes: both of our lines joined, a
     * Thai house number's "/" turned into "-" (refused as-is), and every other
     * refused symbol dropped.
     */
    public static function addressLine(?string $line1, ?string $line2 = null): string
    {
        $joined = self::arabicDigits(trim($line1 . ' ' . $line2));
        $joined = str_replace(['/', '\\'], '-', $joined);
        $joined = (string) preg_replace(self::ADDRESS_FORBIDDEN, ' ', $joined);

        return trim((string) preg_replace('/\s+/u', ' ', $joined));
    }

    /** "อ.เมือง" → "อำเภอเมือง"; other refused symbols become spaces. */
    public static function city(?string $value): string
    {
        $value = trim((string) $value);
        $value = (string) preg_replace('/^อ\.\s*/u', 'อำเภอ', $value);
        $value = (string) preg_replace('/^ข\.\s*/u', 'เขต', $value);
        $value = (string) preg_replace(self::CITY_FORBIDDEN, ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function companyName(?string $value): string
    {
        $value = (string) preg_replace(self::ADDRESS_FORBIDDEN, ' ', (string) $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /** Country calling code, digits only: "+66" → "66", empty → the country's. */
    public static function phoneCc(?string $value, ?string $country = null): string
    {
        $digits = (string) preg_replace('/\D/', '', self::arabicDigits($value));

        if ($digits === '') {
            $digits = (string) (self::country($country)['phone_cc'] ?? '');
        }

        return $digits;
    }

    /**
     * The national number, digits only and without its trunk "0". A number
     * pasted with its country code in front ("+66 81 234 5678") loses that too.
     */
    public static function phoneNumber(?string $value, string $phoneCc): string
    {
        $digits = (string) preg_replace('/\D/', '', self::arabicDigits($value));

        if ($phoneCc !== '' && str_starts_with($digits, $phoneCc) && strlen($digits) > strlen($phoneCc) + 7) {
            $digits = substr($digits, strlen($phoneCc));
        }

        return ltrim($digits, '0');
    }

    public static function zip(?string $value, ?string $country): string
    {
        $code = strtoupper((string) $country);
        $zip = strtoupper(trim((string) preg_replace('/\s+/', ' ', self::arabicDigits($value))));

        return match ($code) {
            'JP' => preg_match('/^\d{7}$/', $zip) ? substr($zip, 0, 3) . '-' . substr($zip, 3) : $zip,
            'GB' => (! str_contains($zip, ' ') && strlen($zip) >= 5) ? substr($zip, 0, -3) . ' ' . substr($zip, -3) : $zip,
            'HK' => (string) preg_replace('/[^A-Z0-9]/', '', $zip),
            default => $zip,
        };
    }

    /**
     * The registrar's spelling of a region, from whatever the customer (or
     * their browser's autofill) typed: "กรุงเทพมหานคร", "จ.เชียงใหม่",
     * "Chon Buri" and "Chonburi" all resolve. Null when nothing matches.
     */
    public static function region(?string $country, ?string $value): ?string
    {
        $spec = self::country($country);
        $needle = self::regionKey($value);

        if (! $spec || $needle === '') {
            return null;
        }

        foreach ($spec['regions'] as $registrar => $label) {
            if (self::regionKey((string) $registrar) === $needle || self::regionKey((string) $label) === $needle) {
                return (string) $registrar;
            }
        }

        if (strtoupper((string) $country) === 'TH') {
            $alias = self::TH_ALIASES[$needle] ?? null;

            if ($alias !== null && isset($spec['regions'][$alias])) {
                return $alias;
            }
        }

        return null;
    }

    /** The province a Thai postcode belongs to, as the registrar spells it. */
    public static function thaiProvinceForPostcode(?string $zip): ?string
    {
        $zip = self::arabicDigits(trim((string) $zip));

        if (! preg_match('/^\d{5}$/', $zip)) {
            return null;
        }

        return config('domain_whois.th_postcode_exceptions.' . $zip)
            ?? config('domain_whois.th_postcode_prefixes.' . substr($zip, 0, 2));
    }

    private static function regionKey(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = (string) preg_replace('/^(?:จังหวัด|จ\.)\s*/u', '', $value);
        $value = (string) preg_replace('/\s+(?:province|prefecture|state)$/u', '', $value);

        return (string) preg_replace('/[\s.\'’-]+/u', '', $value);
    }

    // ----------------------------------------------------------- the payload

    /**
     * `whois_details` for this contact, in the registrar's keys.
     *
     * @return array<string,string>
     */
    public static function details(DomainContact $contact): array
    {
        $country = strtoupper((string) $contact->country);
        $spec = self::country($country) ?? [];
        $phoneCc = self::phoneCc($contact->phone_country_code, $country);

        $details = [
            'first_name' => self::name($contact->first_name),
            'last_name' => self::name($contact->last_name),
            'email' => trim((string) $contact->email),
            'address' => self::addressLine($contact->address1, $contact->address2),
            'city' => self::city($contact->city),
            'country_code' => $country,
            'phone_cc' => $phoneCc,
            'phone_number' => self::phoneNumber($contact->phone, $phoneCc),
            ($spec['state_key'] ?? 'state') => self::region($country, $contact->state) ?? (string) $contact->state,
            ($spec['zip_key'] ?? 'zip') => self::zip($contact->zip, $country),
        ];

        $company = self::companyName($contact->organization);

        if ($company !== '') {
            $details['company_name'] = $company;
        }

        return $details;
    }

    // ------------------------------------------------------------- the rules

    /**
     * What the registrar would refuse, as [form field => Thai message].
     * Empty means it will be accepted.
     *
     * @param  DomainContact|array<string,mixed>  $contact
     * @return array<string,string>
     */
    public static function problems(DomainContact|array $contact): array
    {
        $get = fn (string $key) => $contact instanceof DomainContact
            ? $contact->getAttribute($key)
            : ($contact[$key] ?? null);

        $problems = [];
        $country = strtoupper((string) $get('country'));
        $spec = self::country($country);

        foreach (['first_name' => 'ชื่อ', 'last_name' => 'นามสกุล'] as $field => $label) {
            $name = self::name($get($field));
            $length = mb_strlen($name);

            if ($name === '') {
                $problems[$field] = "กรุณากรอก{$label}";
            } elseif ($length < self::NAME_MIN || $length > self::NAME_MAX) {
                $problems[$field] = "{$label}ต้องยาว " . self::NAME_MIN . '–' . self::NAME_MAX . ' ตัวอักษร';
            } elseif (! preg_match('/^[\pL\pM]+(?:[ -][\pL\pM]+)*$/u', $name)) {
                $problems[$field] = "{$label}ใช้ได้เฉพาะตัวอักษรไทยหรืออังกฤษ ห้ามมีตัวเลขหรือเครื่องหมาย (ยกเว้นขีด -)";
            }
        }

        $email = trim((string) $get('email'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $problems['email'] = 'กรุณากรอกอีเมลให้ถูกต้อง เช่น name@gmail.com';
        }

        $phoneCc = self::phoneCc($get('phone_country_code'), $country);
        $phone = self::phoneNumber($get('phone'), $phoneCc);

        if ($phoneCc === '' || strlen($phoneCc) > 4) {
            $problems['phone_country_code'] = 'รหัสประเทศของเบอร์โทรไม่ถูกต้อง';
        }

        if (strlen($phone) < 6 || strlen($phone) > 14) {
            $problems['phone'] = 'กรุณากรอกเบอร์โทรให้ครบ เช่น 081 234 5678';
        }

        $address = self::addressLine($get('address1'), $get('address2'));

        if ($address === '') {
            $problems['address1'] = 'กรุณากรอกที่อยู่';
        } elseif (mb_strlen($address) > self::ADDRESS_MAX) {
            $problems['address1'] = 'ที่อยู่รวมกันยาว ' . mb_strlen($address) . ' ตัวอักษร ทะเบียนรับได้ไม่เกิน '
                . self::ADDRESS_MAX . ' — ลองย่อ เช่น ถนน → ถ. ซอย → ซ. หมู่ → ม. ตำบล → ต.';
        } elseif (! preg_match('/\d/', $address)) {
            $problems['address1'] = 'ที่อยู่ต้องมีบ้านเลขที่ (ตัวเลข) อย่างน้อยหนึ่งตัว';
        } elseif (! preg_match('/\pL/u', $address)) {
            $problems['address1'] = 'ที่อยู่ต้องมีตัวอักษรด้วย เช่น ชื่อถนนหรือตำบล';
        }

        $city = self::city($get('city'));

        if ($city === '') {
            $problems['city'] = 'กรุณากรอกเขต/อำเภอ';
        } elseif (mb_strlen($city) > self::CITY_MAX) {
            $problems['city'] = 'เขต/อำเภอยาวเกิน ' . self::CITY_MAX . ' ตัวอักษร';
        }

        if (! $spec) {
            $problems['country'] = 'ยังไม่รองรับประเทศนี้ กรุณาเลือกจากรายการ';

            return $problems;
        }

        if (self::region($country, $get('state')) === null) {
            $problems['state'] = 'กรุณาเลือก' . $spec['state_label']['th'] . 'จากรายการ';
        }

        $zip = self::zip($get('zip'), $country);

        if (! preg_match($spec['zip_pattern'], $zip)) {
            $problems['zip'] = 'รหัสไปรษณีย์ไม่ถูกต้อง ตัวอย่างที่ถูก: ' . $spec['zip_example'];
        }

        return $problems;
    }
}
