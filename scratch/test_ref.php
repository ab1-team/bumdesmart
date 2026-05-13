<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Utils\ReferenceUtil;
use App\Models\Purchase;

// Mock auth
$user = \App\Models\User::first();
auth()->login($user);

$date = "2026-04-29";
$res = ReferenceUtil::generate(Purchase::class, 'PO', 'no_pembelian', 'tanggal_pembelian', $date);
echo "Result for $date: $res\n";

$date2 = "2026-05-13";
$res2 = ReferenceUtil::generate(Purchase::class, 'PO', 'no_pembelian', 'tanggal_pembelian', $date2);
echo "Result for $date2: $res2\n";
