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
 * @property string meaning
 * @property float priority
 * @property string created_at
 * @property string updated_at
 */
class Meaning extends Model
{
    protected $table = 'meaning';
}
