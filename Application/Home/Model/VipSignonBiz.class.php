<?php
/**
 * Created by PhpStorm.
 * User: xiedalie
 * Date: 2017/3/21
 * Time: 10:24
 */

namespace Home\Model;


use Vendor\Hiland\Utils\Data\DateHelper;
use Vendor\Hiland\Utils\DataModel\ModelMate;

class VipSignonBiz
{
    public static function signOn($vipid = 0,out $signOrder,out $score,out $returnMessage)
    {
        $signMate = new ModelMate('vip_signon_detail');

        $today = DateHelper::format(null, 'Y-m-d');
        $nextDay = DateHelper::format(DateHelper::addInterval(null, 'd', 1), 'Y-m-d');

        $filter['signtime'] = array('between', array($today, $nextDay));
        $filter['vipid'] = $vipid;

        $recordExist = $signMate->find($filter);

        if (!$recordExist) {
            //如果时间在8点-10点之间，那么折半积分
            $timestamp_5 = DateHelper::getTimestamp(date('Y-m-d') . ' 5:0:0');

            $timestamp_8 = DateHelper::getTimestamp(date('Y-m-d') . ' 8:0:0');
            $timestamp_10 = DateHelper::getTimestamp(date('Y-m-d') . ' 10:0:0');

            $currentTime = time();

            if ($currentTime < $timestamp_5) {
                $returnMessage ='目前签到时间尚未开始,请每天早上5点后前来签到.';
                return false;
            }else{
                $continueDayCount = self::getContinuousDayCount($vipid) + 1;
                $basicScore = (int) C('VIP_SIGNON_BASICSCORE');




                $signOrder = self::getFriendsSignonCount($vipid) + 1;
                $scoreOfToday = $basicScore * $continueDayCount;


                if ($currentTime >= $timestamp_5 && $currentTime < $timestamp_8) {
                    //本时间段内，完全积分。
                    $returnMessage ="今天是您连续第($continueDayCount)天签到.今日签到时间在您朋友圈中排行第($signOrder)名,本时段(早上5-8点)内签到获得完全积分($scoreOfToday)分.";
                }

                if ($currentTime >= $timestamp_8 && $currentTime < $timestamp_10) {
                    //本时间段内，0.5倍积分。
                    $scoreOfToday = $scoreOfToday / 2;
                    $returnMessage ="今天是您连续第($continueDayCount)天签到。今日签到时间在您朋友圈中排行第($signOrder)名,本时段(早上8-10点)内签到获得折半积分($scoreOfToday)分.";
                }

                if ($currentTime >= $timestamp_10) {
                    //超过10点不进行积分。
                    $scoreOfToday = 0;
                    $returnMessage ="今天是您连续第($continueDayCount)天签到。今日签到时间在您朋友圈中排行第($signOrder)名,本时段(早上10点后)内签到不获得积分.";
                }


                $record['score'] = $scoreOfToday;
                $score= $scoreOfToday;

                $record['vipid'] = $vipid;
                $record['signtime'] = date('Y-m-d H:i:s');
                $record['scoretype'] = 1;
                $record['signOrder'] = $signOrder;


                $signMate->interact($record);

                $summaryMate = new ModelMate('vip_signon_summary');
                $summaryCondition['vipid'] = $vipid;
                $summary = $summaryMate->find($summaryCondition);
                if ($summary) {
                    $summary['totalscore'] = $summary['totalscore'] + $scoreOfToday;
                } else {
                    $summary['vipid'] = $vipid;
                    $summary['totalscore'] = $record['score'];
                }

                return $summaryMate->interact($summary);
            }
        }
    }

    public static function getContinuousDayCount($vipid = 0)
    {
        $signMate = new ModelMate('vip_signon_detail');
        $filter['vipid'] = $vipid;
        $signArray = $signMate->select($filter, 'signtime desc', 0, 0, 22);

        //先计算不包括当前日的连续天数
        $result= self::calcContinuousDayCount($signArray);

        //再计算包括当前日的连续天数
        if($result==0){
            $nextDate=  DateHelper::addInterval(null,'d',1);
            $nextDate= DateHelper::format($nextDate,'Y-m-d 0:0:0');
            $result= self::calcContinuousDayCount($signArray,$nextDate);
        }

        return $result;
    }

    private static function calcContinuousDayCount($signArray, $comparingDate = null)
    {
        if ($comparingDate == null) {
            $comparingDate = DateHelper::format(null, 'Y-m-d 0:0:0');
        }

        $comparingDate = DateHelper::getTimestamp($comparingDate);

        $recordCount = count($signArray);
        $continuousDays = 0;
        for ($i = 0; $i < $recordCount; $i++) {
            $record = strtotime($signArray[$i]['signtime']);

            $beginTime = DateHelper::addInterval($comparingDate, 'd', -$i - 1);
            $endTime = DateHelper::addInterval($comparingDate, 'd', -$i);

            if ($record >= $beginTime && $record < $endTime) {
                $continuousDays++;
            } else {
                break;
            }
        }

        return $continuousDays;
    }

    /**
     * @param $vip
     * @param null $beginDate 开始计算日期，默认为null表示今天 （时刻为0点0分0秒）
     * @param null $endDate 结束计算日期，默认为null表示$beginDate的当天 （时刻为次日0点0分0秒）
     * @return mixed
     */
    public static function getFriendsSignonCount($vip,$beginDate=null,$endDate=null){
        if($beginDate==null){
            $beginDate= DateHelper::format(null,'Y-m-d 0:0:0');
        }

        if($endDate==null){
            $beginDateStamp= DateHelper::getTimestamp($beginDate);
            $endDateStamp= DateHelper::addInterval($beginDateStamp,'d',1);
            $endDate= DateHelper::format($endDateStamp,'Y-m-d 0:0:0');
        }

        $friends= VipFriendsBiz::getMyFriends($vip);
        //$friendsCount= count($friends);

        $condition = null;
        $inClause= '-110';//占位符，因为-110这个id根本不会存在
        foreach ($friends as $friend){
            $inClause.= ','.$friend;
        }

        if($inClause){
            $condition['vipid']= array('in',$inClause);
        }

        $condition['signtime'] = array('between',"$beginDate,$endDate");

        dump($condition);
        $mate= new ModelMate('vip_signon_detail');
        $result= $mate->getCount($condition);

        return $result;
    }
}