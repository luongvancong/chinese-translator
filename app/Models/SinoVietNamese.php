<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SinoVietNamese
 * @package App\Models
 *
 * @property string id
 * @property string word
 * @property string sino
 * @property string created_at
 * @property string updated_at
 */
class SinoVietNamese extends Model
{
    protected $table = 'sino_vietnamese';
    protected $keyType = 'string';
    public $incrementing = false;
}
