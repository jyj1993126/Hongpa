<?php

namespace App\Kernel\Network;

/**
 * @author Leon J
 * @since 2017/5/16
 */
/**
 * Class SwooleRequestConvertor
 * @package App\Kernel\Network
 */
class SwooleRequestConvertor
{
    /**
     * @param \swoole_http_request $request
     * @return \Illuminate\Http\Request
     */
    public static function convert(\swoole_http_request $request)
    {
        self::ucHeaders($request);
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $server = isset($request->server) ? $request->server : [];
        $header = isset($request->header) ? $request->header : [];
        $files = isset($request->files) ? $request->files : [];
        // $attr = isset($request->files) ? $request->files : [];
        
        $content = $request->rawContent() ?: null;
        
        return new \Illuminate\Http\Request(
            $get, $post, []/* attributes */, $cookie, $files, $server, $content
        );
    }
    
    /**
     * @param $request
     * @return mixed
     */
    protected static function ucHeaders($request)
    {
        // merge headers into server which ar filted by swoole
        // make a new array when php 7 has different behavior on foreach
        $new_header = [];
        $uc_header = [];
        foreach ($request->header as $key => $value) {
            $new_header['http_' . $key] = $value;
            $uc_header[ucwords($key, '-')] = $value;
        }
        $server = array_merge($request->server, $new_header);
        
        // swoole has changed all keys to lower case
        $server = array_change_key_case($server, CASE_UPPER);
        $request->server = $server;
        $request->header = $uc_header;
        return $request;
    }
}
