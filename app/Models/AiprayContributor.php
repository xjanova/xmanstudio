<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One Aipray app install that uploaded voice. No personal data: the id is
 * random, and key_hash is the SHA-256 of a secret kept on the phone.
 */
class AiprayContributor extends Model
{
    protected $table = 'aipray_contributors';

    protected $fillable = ['contributor_id', 'key_hash'];

    protected $hidden = ['key_hash'];

    public function ownsSecret(string $secret): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $secret));
    }
}
