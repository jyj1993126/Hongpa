<?php
/**
 * @author Leon J
 * @since 2017/5/9
 */

return [
    environment('production') ? null : App\Service\Migrate\MigrateService::class
];
