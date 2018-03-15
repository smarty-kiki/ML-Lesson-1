<?php

function make_up_item_and_count($record, $phase_num, $rule_delimiter, &$make_up_counts)
{/*{{{*/
    $count = count($record);

    $max_phase_index = $phase_num - 1;

    $model = range(0, $max_phase_index);

    $now_phase_index = $max_phase_index;

    while (true) {

        if ($model[$now_phase_index] < ($count - ($max_phase_index - $now_phase_index))) {

            if ($now_phase_index === $max_phase_index) {
                $rule_arr = [];
                foreach ($model as $index) {
                    $rule_arr[] = $record[$index];
                }

                $rule = implode($rule_delimiter, $rule_arr);

                if (! array_key_exists($rule, $make_up_counts)) {
                    $make_up_counts[$rule] = 0;
                }

                $make_up_counts[$rule]++;

                $model[$now_phase_index]++;
            } else {
                $model[$now_phase_index]++;

                for ($i = $now_phase_index + 1; $i <= $max_phase_index; $i ++) {
                    $model[$i] = $model[$i - 1] + 1;
                }

                $now_phase_index = $max_phase_index;
            }
        } else {
            $now_phase_index --;

            if ($now_phase_index < 0) {
                break;
            }
        }
    }
}/*}}}*/

function cal_phase_make_up_counts($records, $phase_num, $rule_delimiter)
{/*{{{*/
    $res = [];

    foreach ($records as $record) {
        make_up_item_and_count($record, $phase_num, $rule_delimiter, $res);
    }

    return $res;
}/*}}}*/

/**
 * apriori
 *
 * @param array $records  数据记录
 * @param int $max_phase  项中元素的最大个数
 * @param float $min_support  最小支持度
 * @param float $min_confidience  最小置信度
 * @param string $rule_delimiter  生成的规则里的元素分隔符
 * @access public
 * @return void
 */
function apriori(array $records, int $max_phase = 3, float $min_support = 0.1, float $min_confidience = 0.6, string $rule_delimiter = ',')
{/*{{{*/
    $supports = [];
    $confidiences = [];
    $make_up_counts = [];
    $lifts = [];

    $record_count = count($records);
    $min_support_count = $record_count * $min_support;

    $phase_num = 0;
    while (++$phase_num <= $max_phase) {

        $make_up_counts[$phase_num] = [];
        $supports[$phase_num] = [];
        $confidiences[$phase_num] = [];
        $lifts[$phase_num] = [];

        $phase_make_up_counts = cal_phase_make_up_counts($records, $phase_num, $rule_delimiter);

        foreach ($phase_make_up_counts as $rule => $count) {

            // 满足最小支持度的数据
            if ($count > $min_support_count) {

                // 计入 count
                $make_up_counts[$phase_num][$rule] = $count;

                // 计入支持度数组
                $supports[$phase_num][$rule] =
                    // 支持度逻辑
                    $support = $count / $record_count;

                // 当前阶不是第一阶时
                if ($phase_num > 1) {

                    $last_phase = $phase_num - 1;

                    $max_confidience_from_rule_count = $count / $min_confidience;

                    // 拆解 rule 遍历得出其上一阶的项
                    $rule_item = explode($rule_delimiter, $rule);

                    for ($i = 0; $i < $phase_num; $i ++) {

                        $from_rule_item = $rule_item;

                        array_splice($from_rule_item, $i, 1);

                        $from_rule = implode($rule_delimiter, $from_rule_item);

                        if (array_key_exists($from_rule, $make_up_counts[$last_phase])) {

                            $from_rule_count = $make_up_counts[$last_phase][$from_rule];

                            // 上一阶项与当前项计算满足最小置信度的数据
                            if ($from_rule_count <= $max_confidience_from_rule_count) {

                                if (! array_key_exists($from_rule, $confidiences[$last_phase])) {
                                    $confidiences[$last_phase][$from_rule] = [];
                                }

                                // 计入置信度数组
                                $confidiences[$last_phase][$from_rule][$rule] =
                                    // 置信度逻辑
                                    $confidience = $count / $from_rule_count;

                                if (! array_key_exists($from_rule, $lifts[$last_phase])) {
                                    $lifts[$last_phase][$from_rule] = [];
                                }

                                // 计入提升度数组
                                $lifts[$last_phase][$from_rule][$rule] =
                                    // 提升度逻辑
                                    $lift = $confidience / $support;
                            }
                        }

                    } unset ($from_rule_item);

                }
            }

        } unset ($phase_make_up_counts);
    }

    return [
        'item_makes_count' => $make_up_counts,
        'supports' => $supports,
        'confidiences' => $confidiences,
        'lifts' => $lifts,
    ];
}/*}}}*/

function print_supports($supports)
{/*{{{*/
    $res = '';

    foreach ($supports as $phase => $support_info) {
        foreach ($support_info as $rule => $support) {
            $res .= "$rule: $support\n";
        }
    }

    return $res;
}/*}}}*/

function print_confidiences($confidiences)
{/*{{{*/
    $res = '';

    foreach ($confidiences as $phase => $confidiences_info) {
        foreach ($confidiences_info as $from_rule => $to_confidiences) {
            $res .= "$from_rule\n";
            foreach ($to_confidiences as $to_rule => $confidience) {
                $res .= "=> $to_rule: $confidience\n";
            }
        }
    }

    return $res;
}/*}}}*/

function print_lifts($lifts)
{/*{{{*/
    $res = '';

    foreach ($lifts as $phase => $lifts_info) {
        foreach ($lifts_info as $from_rule => $to_lifts) {
            $res .= "$from_rule\n";
            foreach ($to_lifts as $to_rule => $lift) {
                $res .= "=> $to_rule: $lift\n";
            }
        }
    }

    return $res;
}/*}}}*/
