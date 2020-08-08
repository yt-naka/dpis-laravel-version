<?php

/**
 * 横軸のデータ数と等しい回帰直線の縦軸の配列データを返す
 * @param array $horizontal_line_array 横軸1次元配列
 * @param array $vertical_line_array   縦軸1次元配列
 * @return array 回帰直線の縦軸1次元配列データ
 */
function Get_Regression_line($horizontal_line_array, $vertical_line_array)
{
    $start_regression_line_index = -1;
    foreach ($vertical_line_array as $i => $vertical_line) {
        if (!is_null($vertical_line) && $start_regression_line_index === -1) {
            $start_regression_line_index = $i;
        }
        if (!is_null($vertical_line)) {
            $end_regression_line_index = $i;
        }
    }
    try {
        $REGRESSION_LINE_DATA_NUM = $end_regression_line_index
            - $start_regression_line_index + 1;
        $HORIZONTAL_LINE_ARRAY_NUM = count($horizontal_line_array);

        $x_average = 0;
        $y_average = 0;
        $xy_average = 0;
        $x_square_average = 0;
        for ($i = $start_regression_line_index; $i <= $end_regression_line_index; $i++) {
            if (!is_null($vertical_line_array[$i])) {
                $x_average += ($i / $REGRESSION_LINE_DATA_NUM);
                $y_average += ($vertical_line_array[$i] / $REGRESSION_LINE_DATA_NUM);
                $xy_average += (($i * $vertical_line_array[$i])
                    / $REGRESSION_LINE_DATA_NUM);
                $x_square_average += ($i**2 / $REGRESSION_LINE_DATA_NUM);
            }
        }

        $slope = ($xy_average - $x_average * $y_average) /
            ($x_square_average - $x_average**2);
        $y_intercept = - $slope * $x_average + $y_average;

        $regression_line = array();
        for ($i = 0; $i < $HORIZONTAL_LINE_ARRAY_NUM; $i++) {
            if ($start_regression_line_index <= $i && $i <= $end_regression_line_index) {
                $regression_line[] = $y_intercept + $slope * $i;
            } else {
                $regression_line[] = null;
            }
        }
        return $regression_line;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 2020年7月6日から今日までの一日ごとの日付配列を返す
 * @return array 2020年7月6日から今日までの一日ごとの日付1次元配列
 */
function Get_Time_array()
{
    $timestamp = strtotime('2020-07-06');
    $startDate = date('Y-m-d', $timestamp);
    $endDate = date('Y-m-d');
    $diff = (strtotime($endDate) - strtotime($startDate)) / ( 60 * 60 * 24);
    for ($i = 0; $i <= $diff; $i++) {
        $period[] = date('m/d', strtotime($startDate . '+' . $i . 'days'));
    }

    return $period;
}

/**
 * レコードのcreated_atカラムのみが入った配列を返す。関数化しないとコードが冗長になってしまう。
 * @param array $histories レコード2次元連想配列
 * @return array それぞれのcreated_atが入った一次元配列
 */
function Get_Created_At_array($histories)
{
    $product_histories_created_at_array = array();
    foreach ($histories as $history) {
        $product_histories_created_at_array[]
            = $history->created_at->format('m/d');
    }

    return $product_histories_created_at_array;
}

/**
 * ベースの横軸データに基づいて、実際の横軸データと比較しデータがない場所には縦軸データにnullを入れる
 * @param array $base_horizontal_line_array   ベースの横軸1次元配列
 * @param array $actual_horizontal_line_array 実際の横軸1次元配列
 * @param array $record_array                 それぞれのレコードが入った2次元連想配列
 * @return array chart js 用に正規化した縦軸1次元配列データ
 */
function Get_Vertical_Line_array($base_horizontal_line_array, $actual_horizontal_line_array, $record_array)
{
    $vertical_line_array = array();
    foreach ($base_horizontal_line_array as $date) {
        $index = array_search($date, $actual_horizontal_line_array);
        if ($index || $index === 0) {
            $vertical_line_array[] = $record_array[$index]->average_price;
        } else {
            $vertical_line_array[] = null;
        }
    }

    return $vertical_line_array;
}
