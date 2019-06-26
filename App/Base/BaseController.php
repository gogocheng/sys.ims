<?php
namespace App\Base;

use App\Utility\ESTools;
use Lib\Exception\ESException;
use EasySwoole\Http\AbstractInterface\Controller;
use App\Utility\Pool\Mysql\MysqlObject;
use App\Utility\Pool\Mysql\MysqlPool;
use App\Model\IpWhiteListModel;

/**
 * Class BaseController
 * @package App
 */
class BaseController Extends Controller
{
    protected $code = 0;
    protected $message = '';
    protected $data = null;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param string|null $action
     * @return bool|null
     * @throws \Exception
     */
    protected function onRequest(?string $action): ?bool
    {
        $esTool = new ESTools();
        if (parent::onRequest($action)) {
            try {
                // 均需要验证白名单
                $this->checkClientIpHasAccessAuthority();
                // 根据这个做登录什么的限制
                $target = $esTool->parseRequestTarget($this->request());
                if ($target['module'] === 'Admin') {
                    // 后台模块 除登录模块外，均需验证是否处于登录状态
                } else if ($target['module'] === 'Api') {
                    // API模块
                } else {
                    throw new ESException($esTool->lang('module_not_found'));
                }
                $this->code = 200;
            } catch (ESException $e) {
                $this->message = $e->report();
            } catch (\Throwable $e) {
                $this->message = $e->getMessage();
            } finally {
                if ($this->code == 200) {
                    return true;
                } else {
                    $esTool->writeJsonByResponse($this->response(), $this->code, $this->data, $this->message);
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * @param \Throwable $throwable
     */
    protected function onException(\Throwable $throwable): void
    {
        // 清空之前输出缓存
        $this->response()->getBody()->truncate();
        $msg = $throwable->getMessage();
        $this->writeJson(10154, null, $msg);
        return ;
    }

    public function index()
    {
        // TODO: Implement index() method.
        $this->response()->write("forbidden");
        return false;
    }

    /**
     * 檢測客戶端IP是否具有權限訪問
     * @throws ESException
     * @throws \EasySwoole\Component\Pool\Exception\PoolEmpty
     * @throws \EasySwoole\Component\Pool\Exception\PoolException
     * @throws \Throwable
     */
    protected function checkClientIpHasAccessAuthority():void
    {
        $esTool = new ESTools();
        $whiteIp = MysqlPool::invoke(function (MysqlObject $db) use ($esTool) {
            return (new IpWhiteListModel($db))->queryByIpAddr($esTool->getClientIp($this->request()));
        });
        if (is_null($whiteIp))
            throw new ESException($esTool->lang('ip_has_refused'));

        if (!$whiteIp['is_enable'])
            throw new ESException($esTool->lang('ip_has_disable'));
        return ;

    }
}
