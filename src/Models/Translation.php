<?php

namespace Jawabapp\Localization\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jawabapp\Localization\Models\Translation
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $language_code
 * @property string $created_at
 * @property string $updated_at
 */
class Translation extends Model
{
    protected $table = 'jawab_translations';

    protected $fillable = [
        'key',
        'value',
        'language_code'
    ];

    public static $groups = [
        'st_public',
        'doa_data_collect',
        'mobile',
        'email_template',
        'auth',
        'pagination',
        'passwords',
        'validation',
    ];
    
}
