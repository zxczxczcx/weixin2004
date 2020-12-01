<?php

namespace App\Model\Xcx;

use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    protected $table = 'wx_xcx_cart';
    protected $primerykey = 'cart_id';
    public $timestamps = false;
}
