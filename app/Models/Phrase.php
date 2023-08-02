<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Meaning
 * @package App\Models
 *
 * @property string id
 * @property string phrase
 * @property string sino;
 * @property string meaning
 * @property int length
 * @property float priority
 * @property string created_at
 * @property string updated_at
 */
class Phrase extends Model
{
    protected $table = 'phrase';
    protected $keyType = 'string';
    public $incrementing = false;
}
