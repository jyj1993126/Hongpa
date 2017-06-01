<?php

namespace App\Contract;

use App\Kernel\Application;

/**
 * @author Leon J
 * @since 2017/5/9
 */
interface Service
{
    public function boot(Application $app);
}
