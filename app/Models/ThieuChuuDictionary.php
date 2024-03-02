<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ThieuChuuDictionary
 * @package App\Models
 *
 * @property string id
 * @property string word
 * @property string sino
 * @property string meaning
 * @property string meaning_html
 * @property string created_at
 * @property string updated_at
 */
class ThieuChuuDictionary extends Model
{
    protected $table = 'thieu_chuu_dictionary';
}
