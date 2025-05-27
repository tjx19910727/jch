<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/31
 * Time: 10:49
 */

namespace app\AppFactory\Kernel\Support;


class Tree
{
    /**
     * 数据整理成树形数据
     * @param array $list  数据源
     * @param string $pk   主键
     * @param string $pid      上级ID
     * @param string $child    子级数据
     * @param int $root        层级
     * @return array
     */
    public static function generateTree($list, $pk = "id", $pid = "pid", $child = "child", $root = 0)
    {
        $tree = array();
        $packData = array();
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }
        foreach ($packData as $key => $value) {
            if ($value[$pid] == $root) {
                $tree[] = &$packData[$key];
            } else {
                $packData[$value[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }
}