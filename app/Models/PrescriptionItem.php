<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $table = 'prescription_items';
    protected $primaryKey = 'prescription_item_id';

    protected $fillable = [
        'prescription_id', 'medicine_name', 'dosage', 'form',
        'frequency', 'duration', 'quantity', 'instructions',
    ];
}
