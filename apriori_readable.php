<?php

function atom_all_in_array(array $needle, array $haystack)
{/*{{{*/
    foreach ($needle as $atom)
    {
        if (! in_array($atom, $haystack))
        {
            return false;
        }
    }

    return true;
}/*}}}*/

function make_up_atoms($atoms, $basic_array, $max_phase)
{/*{{{*/
    $res = [];

    for (;;) {
        $atom = array_pop($atoms);

        if (is_null($atom)) {
            break;
        }

        $step = array_merge($basic_array, [$atom]);

        if (count($step) > $max_phase) {
            return $res;
        }

        $res[] = $step;

        $deep_make_up_atoms = make_up_atoms($atoms, $step, $max_phase);

        $res = array_merge($res, $deep_make_up_atoms);

        unset($step);
        unset($deep_make_up_atoms);
    }

    return $res;
}/*}}}*/

function get_all_item_makes($records, $max_phase)
{/*{{{*/
    $all_atoms = [];
    $all_item_makes = [];
    $max_phase_in_records = 0;

    // 获取原子内容
    foreach ($records as $line => $record) {

        $max_phase_in_records = max($max_phase_in_records, count($record));

        foreach ($record as $index => $atom) {
            $all_atoms[] = $atom;
        }
    }

    $all_atoms = array_values(array_unique($all_atoms));

    // 通过原子内容组合出最大阶数范围内的项
    return make_up_atoms($all_atoms, [], min($max_phase, $max_phase_in_records));
}/*}}}*/

function get_item_makes_count($all_item_makes, $records, $rule_delimiter)
{/*{{{*/
    $item_makes_count = [];

    foreach ($all_item_makes as $item_make) {

        $rule = implode($rule_delimiter, $item_make);

        foreach ($records as $record) {
            if (atom_all_in_array($item_make, $record)) {
                if (! array_key_exists($rule, $item_makes_count)) {
                    $item_makes_count[$rule] = 0;
                }
                $item_makes_count[$rule]++;
            }
        }
    }

    return $item_makes_count;
}/*}}}*/

function get_supports($item_makes_count, $record_count, $min_support)
{/*{{{*/
    $supports = [];

    foreach ($item_makes_count as $rule => $item_make_count) {

        $support = $item_make_count / $record_count;

        if ($support >= $min_support) {
            $supports[$rule] = $support;
        }
    }

    return $supports;
}/*}}}*/

function get_confidiences($item_makes_count, $min_confidience, $rule_delimiter)
{/*{{{*/
    $confidiences = [];

    foreach ($item_makes_count as $from_rule => $from_item_make_count) {
        foreach ($item_makes_count as $to_rule => $to_item_make_count) {

            if ($from_rule === $to_rule) {
                continue;
            }

            $from_rule_item = explode($rule_delimiter, $from_rule);
            $to_rule_item = explode($rule_delimiter, $to_rule);

            if (count($to_rule_item) === count($from_rule_item) + 1 && atom_all_in_array($from_rule_item, $to_rule_item)) {

                $confidience = $to_item_make_count / $from_item_make_count;

                if ($confidience >= $min_confidience) {

                    if (! array_key_exists($from_rule, $confidiences)) {
                        $confidiences[$from_rule] = [];
                    }

                    $confidiences[$from_rule][$to_rule] = $to_item_make_count / $from_item_make_count;
                }
            }
        }
    }

    return $confidiences;
}/*}}}*/

function get_lifts($confidiences, $supports)
{/*{{{*/
    $lifts = [];

    foreach ($confidiences as $from_rule => $info) {

        foreach ($info as $to_rule => $confidience) {

            if (array_key_exists($to_rule, $supports)) {

                if (! array_key_exists($from_rule, $lifts)) {
                    $lifts[$from_rule] = [];
                }

                $lifts[$from_rule][$to_rule] = $confidience / $supports[$to_rule];
            }
        }
    }

    return $lifts;
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
function apriori(array $records, int $max_phase = 20, float $min_support = 0.1, float $min_confidience = 0.6, string $rule_delimiter = ',')
{/*{{{*/
    $all_item_makes = get_all_item_makes($records, $max_phase);

    $item_makes_count = get_item_makes_count($all_item_makes, $records, $rule_delimiter);

    $supports = get_supports($item_makes_count, count($records), $min_support);

    $confidiences = get_confidiences($item_makes_count, $min_confidience, $rule_delimiter);

    $lifts = get_lifts($confidiences, $supports);

    return [
        'item_makes_count' => $item_makes_count,
        'supports' => $supports,
        'confidiences' => $confidiences,
        'lifts' => $lifts,
    ];
}/*}}}*/

function print_supports($supports)
{/*{{{*/
    $res = '';

    foreach ($supports as $rule => $support) {
        $res .= "$rule = $support\n";
    }

    return $res;
}/*}}}*/

function print_confidiences($confidiences)
{/*{{{*/
    $res = '';

    foreach ($confidiences as $from_rule => $to_confidiences) {
        $res .= "$from_rule\n";
        foreach ($to_confidiences as $to_rule => $confidience) {
            $res .= "=> $to_rule = $confidience\n";
        }
    }

    return $res;
}/*}}}*/
