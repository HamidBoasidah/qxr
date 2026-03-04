<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svc = $app->make(App\Services\ChatService::class);
$result = $svc->getUserConversations(5);
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
