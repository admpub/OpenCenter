<?php
namespace Common\Model;
use Think\Model;

class Base extends Model {
	static protected $_startedTrans = null;

	/**
	 * 生成搜索条件
	 * @param  array  &$where 		where条件数组
	 * @param  string &$keywords 	关键词
	 * @param  string $field 		查询字段，如有多个可用半角逗号或空格隔开
	 * @param  string $pk 			主键字段
	 * @return void
	 * @author swh <swh@admpub.com>
	 */
	static public function genSearchSql(&$where, &$keywords, $field, $pk = 'id') {

		$keywords = trim($keywords);
		if ($keywords === '') {
			return;
		}

		$sql = '';

		if (preg_match('/^[\\d]+((,|\\s)[\\d]+)*$/', $keywords)) {
			//1,2,3或者1 2或者1
			$keywords = trim($keywords, ',');
			$ids = preg_split('/[^\\d]+/', $keywords);
			$ids = array_unique($ids);
			$sql = $pk . ' IN (' . implode(',', $ids) . ')';
		} elseif (preg_match('/^[\\d]+(-[\\d]+)?$/', $keywords)) {
			//范围：1-50
			$ids = explode('-', $keywords);
			$sql = $pk . ' >= ' . $ids[0];
			if (count($ids) > 1 && $ids[1] != 0) {
				if ($ids[0] < $ids[1]) {
					$sql .= ' AND ' . $pk . ' <= ' . $ids[1];
				} else {
					$sql = $pk . ' >= ' . $ids[1] . ' AND ' . $pk . ' <= ' . $ids[0];
				}
			}
		} else {
			//a "b d e" f
			$fields = preg_split('/[,\\s]+/', $field);
			$kw = preg_replace_callback('/"([^"]+)"/', function ($p) use (&$sql, $fields) {
				foreach ($fields as $f) {
					if ($sql) {
						$sql .= ' OR ';
					}
					$sql .= $f . ' LIKE \'%' . addcslashes($p[1], '\\_%\'') . '%\'';
				}
				return '';
			}, $keywords);
			if ($kw) {
				$arr = preg_split('/[\\s]+/', $kw);
				$arr = array_unique($arr);
				$arr = array_map(function ($item) {
					return addcslashes($item, '\\_%\'');
				}, $arr);
				foreach ($fields as $f) {
					if ($sql) {
						$sql .= ' OR ';
					}

					$sql .= $f . ' LIKE \'%' . implode('%\' OR ' . $f . ' LIKE \'%', $arr) . '%\'';
				}
			}
		}
		if (!empty($where['_string'])) {
			$where['_string'] .= ' AND (' . $sql . ')';
		} else {
			$where['_string'] = $sql;
		}
	}

	/**
	 * 便捷分页查询
	 * @access public
	 * @param mixed $options 表达式参数
	 * @param mixed $pageopt 分页参数
	 * @return mixed
	 * @modified by swh <swh@admpub.com>
	 */
	public function findPage($pagesize, $count = false, $options = array()) {
		// 分析表达式
		$options = $this->_parseOptions($options);

		// 如果没有传入总数，则自动根据条件进行统计
		if ($count === false) {
			// 查询总数
			$count_options = $options;

			// 去掉统计时的排序提高效率
			unset($count_options['order']);

			// 采用group子句或distinct函数时查询总数需要特别处理
			if (!empty($count_options['distinct']) || !empty($count_options['group'])) {
				$sql = $this->db->buildSelectSql($count_options);
				$count_options = array(
					'field' => 'count(1) as count',
					'table' => '(' . $sql . ') _temp_table_',
				);
			} else {
				$count_options['limit'] = 1;
				$count_options['field'] = 'count(1) as count';
			}

			$result = $this->db->select($count_options);

			$count = is_array($result) ? $result[0]['count'] : 0;
			unset($result, $count_options);
		}
		$output = array();
		// 如果查询总数大于0
		if ($count > 0) {
			// 解析分页参数
			if (!is_numeric($pagesize)) {
				$pagesize = 10;
			}

			$p = new \Think\Page($count, $pagesize); // 实例化分页类 传入总记录数和每页显示的记录数
			// 查询数据
			$options['limit'] = $p->firstRow . ',' . $p->listRows;

			// 输出控制
			$output['count'] = $count;
			$output['html'] = $p->show();
			$output['totalPages'] = $p->totalPages;
			$output['totalRows'] = $p->totalRows;
			$output['nowPage'] = $p->nowPage;

			//上面的$this->_parseOptions()已经把$this->options的值清空了，所以这里要重新赋值
			$this->options = &$options;

			//如果有别名且别名已经加入到table元素中了，这里要清除alias元素，否则sql会拼接错误
			if (isset($this->options['alias']) && preg_match('/[\\s]+' . $this->options['alias'] . '$/', $this->options['table'])) {
				unset($this->options['alias']);
			}

			$resultSet = $this->page($p->nowPage, $pagesize)->select();

			//用完清空
			$this->options = array();

			if ($resultSet) {
				$this->dataList = $resultSet;
			} else {
				$resultSet = '';
			}
			$output['data'] = $resultSet;
			unset($resultSet, $p, $count);
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
	public function executeSqlFile($file, $stop = true, $db_charset = 'utf-8') {
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

		$sql = str_replace("\r", "\n", str_replace('`ocenter_', '`' . $this->tablePrefix, $sql));

		foreach (explode(";\n", trim($sql)) as $query) {
			$query = trim($query);
			if (empty($query)) {
				continue;
			}
			$query = preg_replace('/[\r\n]+[ \t]*--[^\n]*\n/', '', $query);
			$query = preg_replace('/^[ \t]*--[^\n]*\n/', '', $query);
			$query = trim($query);
			if (empty($query)) {
				continue;
			}

			$res = $this->execute($query);
			if ($res === false) {
				$error[] = array(
					'error_code' => $this->getDbError(),
					'error_sql' => $query,
				);

				if ($stop) {
					return $error;
				}

			}
		}
		return $error;
	}

	/**
	 * 启动事务
	 * @access public
	 * @return void
	 */
	public function begin($tag = 'default') {
		if (self::$_startedTrans && self::$_startedTrans != $tag) {
			return;
		}
		self::$_startedTrans = $tag;
		parent::startTrans();
	}

	/**
	 * 提交事务
	 * @access public
	 * @return boolean
	 */
	public function end($success = true, $tag = 'default') {
		if ($success) {
			if (self::$_startedTrans != $tag) {
				return;
			}
			self::$_startedTrans = null;
			return parent::commit();
		} else {
			self::$_startedTrans = null;
			return parent::rollback();
		}
	}
}