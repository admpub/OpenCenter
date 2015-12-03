<?php
namespace Common\Model;
use Think\Model;
class Base extends Model{
	
    /**
     * 便捷分页查询
     * @access public
     * @param mixed $options 表达式参数
     * @param mixed $pageopt 分页参数
     * @return mixed
     */
    public function findPage($pageopt, $count = false, $options = array())
    {
        // 分析表达式
        $options = $this->_parseOptions($options);

        // 如果没有传入总数，则自动根据条件进行统计
        if ($count === false) {
            // 查询总数
            $count_options = $options;
            $count_options['limit'] = 1;
            $count_options['field'] = 'count(1) as count';
            // 去掉统计时的排序提高效率
            unset($count_options['order']);
            $result = $this->db->select($count_options);

            $count = $result[0]['count'];
            unset($result);
            unset($count_options);
        }

        // 如果查询总数大于0
        if ($count > 0) {
            // 载入分页类
            //import('ORG.Util.Page');
            // 解析分页参数
            if (is_numeric($pageopt)) {
                $pagesize = intval($pageopt);
            } else {
                $pagesize = 10;
            }

            $p = new \Think\Page($count, $pageopt);// 实例化分页类 传入总记录数和每页显示的记录数
            // 查询数据
            $options['limit'] = $p->firstRow . ',' . $p->listRows;


            // 输出控制
            $output['count'] = $count;
            $output['totalPages'] = $p->totalPages;
            $output['totalRows'] = $p->totalRows;
            $output['nowPage'] = $p->nowPage;
            $output['html'] = $p->show();
            $resultSet = $this->where($options['where'])->order($options['order'])->page($p->nowPage, $pageopt)->select();
            if ($resultSet) {
                $this->dataList = $resultSet;
            } else {
                $resultSet = '';
            }
            $output['data'] = $resultSet;
            unset($resultSet);
            unset($p);
            unset($count);
        } else {
            $output['count'] = 0;
            $output['totalPages'] = 0;
            $output['totalRows'] = 0;
            $output['nowPage'] = 1;
            $output['html'] = '';
            $output['data'] = '';
        }
        // 输出数据
        return $output;
    }


    /**
     * 执行SQL文件
     * @access public
     * @param string  $file 要执行的sql文件路径
     * @param boolean $stop 遇错是否停止  默认为true
     * @param string  $db_charset 数据库编码 默认为utf-8
     * @return array
     */
    public function executeSqlFile($file, $stop = true, $db_charset = 'utf-8')
    {
        $error = true;
        if (!is_readable($file)) {
            $error = array(
                'error_code' => 'SQL文件不可读',
                'error_sql' => '',
            );
            return $error;
        }

        $fp = fopen($file, 'rb');
        $sql = fread($fp, filesize($file));
        fclose($fp);

        $sql = str_replace("\r", "\n", str_replace('`' . 'ocenter_', '`' . $this->tablePrefix, $sql));

        foreach (explode(";\n", trim($sql)) as $query) {
            $query = trim($query);
            if ($query) {
                // if (substr($query, 0, 12) == 'CREATE TABLE') {
                //预处理建表语句
                //      $db_charset = (strpos($db_charset, '-') === FALSE) ? $db_charset : str_replace('-', '', $db_charset);
                //       $type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $query));
                //       $type = in_array($type, array("MYISAM", "HEAP")) ? $type : "MYISAM";
                /* $_temp_query = preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $query) .*/
                //          (mysql_get_server_info() > "4.1" ? " ENGINE=$type DEFAULT CHARSET=$db_charset" : " TYPE=$type");

                // dump($_temp_query);exit;
                //     $res = $this->execute($_temp_query);
                //  } else {
                $res = $this->execute($query);
                //  }
                if ($res === false) {
                    $error[] = array(
                        'error_code' => $this->getDbError(),
                        'error_sql' => $query,
                    );

                    if ($stop) return $error;
                }
            }
        }
        return $error;
    }

}