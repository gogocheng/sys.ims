<?php
namespace App\Base;

use App\Model\SystemManagersModel;
use App\Utility\ESTools;
use Lib\Exception\ESException;
use EasySwoole\Http\AbstractInterface\Controller;
use App\Utility\Pool\Mysql\MysqlObject;
use App\Utility\Pool\Mysql\MysqlPool;
use App\Model\IpWhiteListModel;
use Lib\Logistic;

/**
 * Class BaseController
 * @package App
 */
class BaseController Extends Controller
{
    protected $logisticCode = 0;
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
        if (parent::onRequest($action)) {
            try {
                // to parse uri
                $target = ESTools::parseRequestTarget($this->request());
                if ($target['module'] === 'Api') {
                    // to check action which need to login
                    if ($target['action'] !== 'login') {
                        $esToken = ESTools::getArgFromRequest($this->request(), ['es-token'], 'getHeaders');
                        if (empty($esToken) || !isset($esToken['es-token']) || !isset($esToken['es-token'][0])) {
                            throw new ESException(
                                Logistic::getMsg(Logistic::L_NOT_FOUND),
                                Logistic::L_NOT_FOUND
                            );
                        }
                        MysqlPool::invoke(function (MysqlObject $db) use ($esToken) {
                            return (new SystemManagersModel($db))->checkManagerLoginState($esToken['es-token'][0]);
                        });
                        unset($esToken);
                    }
                } else if ($target['module'] === 'Open') {
                    if ($target['controller'] !== 'Git') {
                        // nothing
                    }
                } else {
                    throw new ESException(Logistic::getMsg(Logistic::L_MODULE_NOT_FOUND), Logistic::L_MODULE_NOT_FOUND);
                }
                $this->logisticCode = Logistic::L_OK;
            } catch (ESException $e) {
                $this->message = $e->report();
                $this->logisticCode = $e->getCode();
            } catch (\Throwable $e) {
                $this->message = $e->getMessage();
                $this->logisticCode = $e->getCode();
            } finally {
                if ($this->logisticCode == Logistic::L_OK) {
                    return true;
                } else {
                    ESTools::writeJsonByResponse($this->response(), $this->logisticCode, $this->message);
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
        $msg = $throwable->getMessage();
        ESTools::writeJsonByResponse($this->response(), Logistic::L_EXCEPTION, $throwable->getMessage());
        return ;
    }

    /**
     * the default index action
     * @return bool
     */
    public function index()
    {
        // TODO: Implement index() method.
        $this->response()->write("forbidden");
        return false;
    }

    /**
     * to check the client ip has permission to enter
     * @throws ESException
     * @throws \EasySwoole\Component\Pool\Exception\PoolEmpty
     * @throws \EasySwoole\Component\Pool\Exception\PoolException
     * @throws \Throwable
     */
    protected function checkClientIpHasAccessAuthority():void
    {
        $clientIp = ESTools::getClientIp($this->request());
        $whiteIp = MysqlPool::invoke(function (MysqlObject $db) use ($clientIp) {
            return (new IpWhiteListModel($db))->queryByIpAddr($clientIp);
        });
        if (is_null($whiteIp))
            throw new ESException(Logistic::getMsg(Logistic::L_IP_NOT_REGISTER), Logistic::L_IP_NOT_REGISTER);

        if (!$whiteIp['is_enable'])
            throw new ESException(Logistic::getMsg(Logistic::L_IP_DISABLE), Logistic::L_IP_DISABLE);
        $this->response()->withHeader('Access-Control-Allow-Origin', long2ip($clientIp));
        return ;

    }
}
