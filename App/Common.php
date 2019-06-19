<?php
namespace App;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;


class Common
{
	public function writeJson($statusCode = 200, $result = null, $msg = null)
	{
		$response = new Response(new \Http\Request);
		$data = Array(
            "code" => $statusCode,
            "data" => $result,
            "msg" => $msg,
            "time" => time()
        );
        $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus($statusCode);
	}
}