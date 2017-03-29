<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{

    protected $table = 'storage_file';
    protected $primaryKey = 'hash';
    protected $fillable = ['name', 'extension', 'mime', 'size', 'hash', 'dimensions', 'upload_time'];

    public $timestamps = false;
    public $incrementing = false;

    public function setDimensionsAttribute($value)
    {
        $this->attributes['dimensions'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getDimensionsAttribute($value)
    {
        return json_decode($value);
    }
}