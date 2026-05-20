<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case InitialStock = 'initial_stock';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
}
