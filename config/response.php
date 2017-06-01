<?php
/**
 * @author Leon J
 * @since 2017/5/19
 */
return [
    'normalizer' => function ($data, $message, $code){
        return compact('data', 'message', 'code');
    },
];
