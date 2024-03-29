<?php
/**
 * Created by PhpStorm.
 * User: speauty
 * Date: 2019/6/25
 * Time: 14:26
 */
namespace App\Base;

use App\Utility\ESTools;
use App\Utility\Pool\Mysql\MysqlObject;
use Lib\Exception\ESException;
use Lib\Logistic;

/**
 * Class BaseModel
 * the basic model
 * @package App\Base
 */
class BaseModel
{
    protected $db;
    protected $softDeleteFieldName = 'delete_at';
    protected $table;

    public function __construct(MysqlObject $dbObject)
    {
        if (is_null($this->db) || !$this->db instanceof MysqlObject) {
            $this->setDb($dbObject);
        }
    }

    private function setDb(MysqlObject $dbObject):void
    {
        $this->db = $dbObject;
    }

    /**
     * to get connection of db
     * @return MysqlObject
     */
    protected function getDb():MysqlObject
    {
        return $this->db;
    }

    /**
     * to get connection of db
     * @return MysqlObject
     */
    public function getDbConnection():MysqlObject
    {
        return $this->db;
    }

    /**
     * 设置过滤软删除数据条件
     * @param string $softDeleteFieldName 软删除字段名
     */
    protected function setSoftDeleteWhere(string $softDeleteFieldName = 'delete_at')
    {
        $this->db->where($this->softDeleteFieldName, 0, '=');
    }

    /**
     * to query data with page
     * @param array $pageConf
     * @param array|null $fieldsName
     * @param array|null $where
     * @param string|null $orderFieldName
     * @param string|null $orderType
     * @param bool|null $setSoftDelete
     * @return array|null
     * @throws \Throwable
     */
    public function queryDataOfPagination(array $pageConf, array $fieldsName = null, array $where = null, string $orderFieldName = null, string $orderType = null, bool $setSoftDelete = null):?array
    {
        if (is_null($orderFieldName)) $orderFieldName = 'id';
        if (is_null($orderType)) $orderType = 'DESC';
        if ($setSoftDelete) {
            $this->setSoftDeleteWhere();
        }
        ESTools::quickParseArr2WhereMap($this->db, $where);
        $this->db->orderBy($orderFieldName, $orderType);
        if ($this->table) {
            $total = $this->db->count($this->table, '1');
            if ($total > 0) {
                $list = $this->db->get(
                    $this->table,
                    [($pageConf['page']-1)*$pageConf['limit'],$pageConf['limit']],
                    is_null($fieldsName)?'*':implode(',',$fieldsName)
                );
            } else {
                $list = null;
            }

        } else {
            $total = 0;
            $list = null;
        }
        return [
            'total' => $total,
            'list' => $list
        ];
    }

    /**
     * to query a record from db
     * @param array|null $fieldsName
     * @param array|null $where
     * @param bool|null $setSoftDelete
     * @return array|null
     * @throws \Throwable
     */
    public function getOne(array $fieldsName = null, array $where = null, bool $setSoftDelete = null):?array
    {
        $setSoftDelete && $this->setSoftDeleteWhere();
        ESTools::quickParseArr2WhereMap($this->db, $where);
        return $this->db->getOne($this->table, is_null($fieldsName)?'*':implode(',',$fieldsName));
    }

    /**
     * to delete a record
     * @param array $where
     * @return bool
     * @throws ESException
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function delete(array $where):bool
    {
        if (!ESTools::checkUniqueByAField($this->db, $this->table, $where)) {
            throw new ESException(
                Logistic::getMsg(Logistic::L_RECORD_NOT_FOUND),
                Logistic::L_RECORD_NOT_FOUND
            );
        }
        ESTools::quickParseArr2WhereMap($this->db, $where);
        return $this->db->delete($this->table, 1);
    }
}