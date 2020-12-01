<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GoodsModel extends Model
{
    protected $table = 'p_goods';
    protected $primaryKey = 'goods_id';
    public $timestamps =false;

    // protected $table = 'p_goods';
    // public $timestamps = false;
    // protected $primaryKey = 'goods_id';
    // protected $guarded = [];   //黑名单  create只需要开启
}
