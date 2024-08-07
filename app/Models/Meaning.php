<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Meaning
 * @package App\Models
 *
 * @property string id
 * @property string word
 * @property string $type
 * @property int word_length
 * @property string meaning
 * @property string sino
 * @property float priority
 * @property string created_at
 * @property string updated_at
 */
class Meaning extends Model
{
    protected $table = 'meaning';
    protected $keyType = 'string';
    public $incrementing = false;

    const TYPE = [
        'NAME' => 'NAME',
        'PHRASE' => 'PHRASE'
    ];
}
