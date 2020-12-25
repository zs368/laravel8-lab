<?php

/**
 * Created by PhpStorm
 * User: ZS
 * Date: 2020/12/18
 * Time: 4:52 下午
 */

if (!function_exists('rrmDir')) {
    /**
     * 递归删除目录
     * @param string $src
     * @return bool
     */
    function rrmDir($src = '')
    {
        if (empty($src)) return true;

        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    rrmDir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}

if (!function_exists('list2tree')) {
    /**
     * 列表结构转树状结构
     * @param        $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int    $root
     * @return array
     */
    function list2tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0): array
    {
        $tree = [];
        if (!is_array($list)) return $tree;

        // 创建基于主键的数组引用
        $refer = [];
        foreach ($list as $key => $value) {
            $refer[$value[$pk]] = &$list[$key];
        }

        foreach ($list as $key => $value) {
            $parentId = $value[$pid];
            if ($parentId == $root) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }

        return $tree;
    }
}

if (!function_exists('tree2list')) {
    /**
     * 树状结构转列表结构
     * @param        $tree
     * @param string $child
     * @return array
     */
    function tree2list($tree, $child = 'children'): array
    {
        static $list = [];
        foreach ($tree as $branch) {
            $tmp = $branch;
            unset($tmp[$child]);
            $list[] = $tmp;
            if (isset($branch[$child]) && !empty($branch[$child])) {
                tree2list($branch[$child]);
            }
        }
        return $list;
    }
}

if (!function_exists('getSubtree')) {
    /**
     * 获取对应子树
     * @param        $tree
     * @param        $name
     * @param string $fileds
     * @param string $child
     * @return array
     */
    function getSubtree($tree, $name, $fileds = 'name', $child = 'children'): array
    {
        $legalTree = [];
        foreach ($tree as $branch) {
            if ($branch[$fileds] == $name) {
                $legalTree = $branch;
                break;
            }
            if (isset($branch[$child])) {
                $legalTree = getSubtree($branch[$child], $name, $fileds, $child);
                break;
            }
        }
        return $legalTree;
    }
}