<?php
/**
 * Created by xLong.
 * Date: 2018/1/30
 * Time: 14:41
 */

namespace lib;

class Db
{
    protected static $_instance;
    protected $pdo;

    private $debug = true;
    private $host = '127.0.0.1';
    private $dbName = 'testa';
    private $userName = 'root';
    private $passwd = 'root';
    private $prefix = '';

    private $rows; // 结果集
    private $lastSql; // 最后一条SQL
    private $values; // 绑定值
    private $lastInsertId; // 插入数据后的ID / 受影响的行数

    private $_select;
    private $_from;
    private $_where;
    private $_where_val = [];
    private $_limit;
    private $_order_by;
    private $_like;
    private $_group_by;
    private $_join;

    private function __construct()
    {
        try {
            $this->pdo = new \PDO("mysql:host=$this->host;dbname=$this->dbName", $this->userName, $this->passwd);
            if ($this->debug) $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // 将错误处理模式变成异常模式
            $this->pdo->query('set names utf8');
        } catch (\PDOException $e) {
            echo '[ERROR] connect: ' . $e->getMessage() . '<br>';
        }
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getLastSql()
    {
        return $this->lastSql;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getNum()
    {
        // 获取受影响的行数、插入记录时的ID
        return $this->lastInsertId;
    }

    private function throwPdoError(PDO $pdo)
    {
        $errorInfo = $pdo->errorInfo();
        if ($errorInfo[0] === "00000") {
            return [];
        }
        throw new \Exception("执行sql语言产生错误错误码:==>{$errorInfo[0]};错误消息:==>{$errorInfo[2]};");
    }

    /**
     * 插入
     * @param $table 目标表
     * @param array $data 插入数据
     * @return string 记录的ID
     */
    public function insert($table, array $data)
    {
        $table = $this->prefix . $table;
        $sql = 'INSERT INTO ' . $table;
        $arrKeys = $arrVals = $arrMark = [];

        foreach ($data as $key => $val) {
            array_push($arrKeys, $key);
            array_push($arrVals, $val);
            array_push($arrMark, '?');
        }

        $strKeys = $strVals = $strMark = '';
        $strKeys = ' (' . implode(',', $arrKeys) . ')';
        $strMark = ' (' . implode(',', $arrMark) . ')';
        $strVals = '[' . implode(',', $arrVals) . ']';
        $sql .= $strKeys;
        $sql .= ' VALUES';
        $sql .= $strMark;

        $prepare = $this->pdo->prepare($sql);
        $prepare->execute($arrVals);

        $this->lastSql = $sql;
        $this->values = $strVals;
        $this->lastInsertId = $this->pdo->lastInsertId();

        return $this->lastInsertId;
    }

    /**
     * 删除
     * @param $table 目标表
     * @param array $where 条件
     * @return int 受影响的行数
     * @throws Exception
     */
    public function delete($table, array $where)
    {
        $table = $this->prefix . $table;
        $sql = 'DELETE FROM ' . $table;

        $arrKeys = $arrVals = [];
        foreach ($where as $key => $val) {
            array_push($arrKeys, $key . '=?');
            array_push($arrVals, $val);
        }

        $strKeys = $strVals = $strMark = '';
        $strKeys = ' ' . implode(' and ', $arrKeys);
        $strVals = '[' . implode(',', $arrVals) . ']';

        $sql .= ' WHERE';
        $sql .= $strKeys;

        $prepare = $this->pdo->prepare($sql);
        $prepare->execute($arrVals);

        $this->lastSql = $sql;
        $this->values = $strVals;
        $rowCount = $prepare->rowCount();

        return $rowCount;
    }

    /**
     * 修改
     * @param $table 目标表
     * @param array $data 修改数据
     * @param array $where 条件
     * @return int 受影响的行数
     * @throws Exception
     */
    public function update($table, array $data, array $where)
    {
        $table = $this->prefix . $table;
        $sql = 'UPDATE ' . $table;

        $arrKeys = $arrVals = [];
        foreach ($data as $key => $val) {
            array_push($arrKeys, $key . '=?');
            array_push($arrVals, $val);
        }

        $strKeys = $strVals = '';
        $strKeys = ' ' . implode(',', $arrKeys);
        $sql .= ' SET';
        $sql .= $strKeys;

        $arrKeys = [];
        foreach ($where as $key => $val) {
            array_push($arrKeys, $key . '=?');
            array_push($arrVals, $val);
        }

        $strKeys = $strVals = '';
        $strKeys = ' ' . implode(' AND ', $arrKeys);
        $strVals = '[' . implode(',', $arrVals) . ']';
        $sql .= ' WHERE';
        $sql .= $strKeys;

        $prepare = $this->pdo->prepare($sql);
        $prepare->execute($arrVals);

        $this->lastSql = $sql;
        $this->values = $strVals;
        $rowCount = $prepare->rowCount();

        return $rowCount;
    }

    // [S] 查询构造

    public function all()
    {
        $arr = [];
        while ($row = $this->rows->fetch(\PDO::FETCH_ASSOC)) {
            $arr[] = $row;
        }
        return $arr;
    }

    public function one()
    {
        return $this->rows->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 执行原生 SQL
     * @param $sql
     * @return $this
     */
    public function query($sql)
    {
        $pattern = '/INSERT|UPDATE|DELETE/i';
        $isWrite = preg_match($pattern, $sql);
        $this->lastSql = $sql;

        if ($isWrite) {
            // 增删改
            $res = $this->pdo->query($this->lastSql);
            if ($this->pdo->lastInsertId() == 0) {
                $this->lastInsertId = $res->rowCount(); // 删改
            } else {
                $this->lastInsertId = $this->pdo->lastInsertId(); // 增
            }
        } else {
            // 查
            $this->rows = $this->pdo->query($this->lastSql);
        }
        return $this;
    }

    /**
     * 执行构造查询
     */
    public function exec()
    {
        $this->creatSql();
        $prepare = $this->pdo->prepare($this->lastSql);
        $bool = $prepare->execute($this->_where_val);
        if (!$bool) $this->throwPdoError($this->pdo);
        $this->rows = $prepare;
        // return $this;
    }

    /**
     * 开始构造 SQL 语句
     */
    public function creatSql()
    {
        // 构建 SELECT
        $sql = $this->_select;
        // 构建 FROM
        $sql .= $this->_from;
        // 构建 JOIN
        $sql .= $this->_join;
        // 构建 WHERE
        if (!empty($this->_where)) {
            $sql .= ' WHERE ' . $this->_where;
        }
        // 构建 LIKE
        if (!empty($this->_like)) {
            if (empty($this->_where)) {
                $sql .= ' WHERE ' . $this->_like;
            } else {
                $sql .= ' AND ' . $this->_like;
            }
        }
        // 构建 GROUP BY
        $sql .= $this->_group_by;
        // 构建 ORDER BY
        $sql .= $this->_order_by;
        // 构建 LIMIT
        $sql .= $this->_limit;

        echo $sql . '<hr>';

        $this->lastSql = $sql;

        $this->resetQuery();
    }

    /**
     * 重置构造数据
     */
    private function resetQuery()
    {
        $this->_select = '';
        $this->_from = '';
        $this->_where = '';
        $this->_where_val = [];
        $this->_limit = '';
        $this->_order_by = '';
        $this->_like = '';
        $this->_group_by = '';
        $this->_join = '';
    }

    /**
     * 构造 SELECT
     * 注意：MYSQL保留字段需要用户自己加反引号 如 from => `from`
     * @param string $select 查询字段
     * @return $this
     */
    public function select($select = '*')
    {
        $this->_select = 'SELECT ' . $select;
        return $this;
    }

    /**
     * 构造 FROM
     * @param $table
     * @return $this
     */
    public function from($table)
    {
        $this->_from = ' FROM ' . $this->prefix . $table;
        return $this;
    }

    /**
     * 构造 WHERE
     * @param array $where
     * @return $this
     */
    public function where($where)
    {
        if (is_array($where)) {
            //数组构造 where
            $arr = $arrKeys = $arrVals = [];
            foreach ($where as $key => $val) {
                $pattern = '/>|>=|<|<=|!=|<>|<=>/i';
                if (preg_match($pattern, $key)) {
                    array_push($arr, $key . ' ?');
                } else {
                    array_push($arr, $key . ' = ?');
                }
                array_push($arrKeys, $key);
                array_push($arrVals, $val);
            }
            $strWhere = implode(' AND ', $arr);

            if (empty($this->_where)) {
                $this->_where = $strWhere;
                $this->_where_val = $arrVals;
            } else {
                $this->_where .= ' AND ' . $strWhere;
                $this->_where_val = array_merge($this->_where_val, $arrVals);
            }

            $this->values = '[' . implode(',', $this->_where_val) . ']';
        } else {
            //自定义构造 where
            $this->_where = $where;
        }

        return $this;
    }

    /**
     * 构造 WHERE OR
     * @param array $condition
     * @return $this
     */
    public function orWhere(array $condition)
    {
        $arr = $arrKeys = $arrVals = [];
        foreach ($condition as $key => $val) {
            $pattern = '/>|>=|<|<=|!=|<>|<=>/i';
            if (preg_match($pattern, $key)) {
                array_push($arr, $key . ' ?');
            } else {
                array_push($arr, $key . ' = ?');
            }
            array_push($arrKeys, $key);
            array_push($arrVals, $val);
        }
        $strWhere = implode(' OR ', $arr);
        if (empty($this->_where)) {
            $this->_where = $strWhere;
            $this->_where_val = $arrVals;
        } else {
            $this->_where .= ' OR ' . $strWhere;
            $this->_where_val = array_merge($this->_where_val, $arrVals);
        }

        $this->values = '[' . implode(',', $this->_where_val) . ']';
        return $this;
    }

    /**
     * 构造 WHERE IN
     * @param array $condition
     * @return $this
     */
    public function whereIn(array $condition)
    {
        $str = '';
        foreach ($condition as $key => $val) {
            $value = [];
            foreach ($val as $k => $v) {
                if (is_string($v)) {
                    $value[] = "'" . $v . "'";
                } else {
                    $value[] = $v;
                }
            }
            $str = $key . ' IN (' . implode(',', $value) . ')';
            if (empty($this->_where)) {
                $this->_where = $str;
            } else {
                $this->_where .= ' AND ' . $str;
            }
        }

        return $this;
    }

    /**
     * 构造 LIMIT
     * @param $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->_limit = " LIMIT $limit OFFSET $offset";
        return $this;
    }

    /**
     * 构造 ORDER BY
     * @param $order
     * @return $this
     */
    public function orderBy($order)
    {
        $this->_order_by = " ORDER BY $order";
        return $this;
    }

    /**
     * 构造 LIKE - 可多LIKE
     * @param $key
     * @param $val
     * @param string $seat both/after/before
     * @return $this
     */
    public function like($key, $val, $seat = "both")
    {
        switch ($seat) {
            case 'before':
                $val = '\'%' . $val . '\'';
                break;
            case 'after':
                $val = '\'' . $val . '%\'';
                break;
            default:
                $val = '\'%' . $val . '%\'';
                break;
        }

        $like = $key . " LIKE " . $val;

        if (!empty($this->_like)) {
            $newLike = $like;
            $like = str_replace(' WHERE ', '', $this->_like) . " AND " . $newLike;
        }

        $this->_like = $like;

        return $this;
    }

    /**
     * 构造 GROUP BY
     * @param $column 列名 str/array
     * @return $this
     */
    public function gourpBy($column)
    {

        if (is_array($column)) {
            $column = implode(',', $column);
        }
        $this->_group_by = ' GROUP BY ' . $column;
        return $this;
    }

    /**
     * 构造 JOIN
     * @param $table 链接表
     * @param $condition 条件
     * @param string $join 连接类型[默认 join] left，right，outer，inner
     * @return $this
     */
    public function join($table, $condition, $join = "join")
    {
        $table = $this->prefix . $table;
        switch ($join) {
            case 'left':
                $join = 'LEFT JOIN';
                break;
            case 'right':
                $join = 'RIGHT JOIN';
                break;
            case 'outer':
                $join = 'OUTER JOIN';
                break;
            case 'inner':
                $join = 'INNER JOIN';
                break;
            default:
                $join = 'JOIN';
        }
        if (empty($this->_join)) {
            $this->_join = ' ' . $join . ' ' . $table . ' ON ' . $condition;
        } else {
            $this->_join .= ' ' . $join . ' ' . $table . ' ON ' . $condition;
        }
        return $this;
    }


}
