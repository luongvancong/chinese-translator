<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SyntaxMeaning
 * @package App\Models
 *
 * @property string id
 * @property string pattern
 * @property int word_length
 * @property string meaning
 * @property float priority
 * @property string created_at
 * @property string updated_at
 */
class SyntaxMeaning extends Model
{
    protected $table = 'syntax_meaning';
}
