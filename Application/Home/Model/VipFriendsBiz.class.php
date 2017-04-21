<?php
/**
 * Created by PhpStorm.
 * User: xiedalie
 * Date: 2017/3/23
 * Time: 9:46
 */

namespace Home\Model;


use Vendor\Hiland\Utils\DataModel\ModelMate;

class VipFriendsBiz
{
    /**
     * @param int $vipid
     * @param int $includeSelf 是否将自己包含在朋友列表中 0：不包含；1：包含
     * @param int $makeFriedsType 建立朋友关系的类型 1：我发出邀请成为的朋友；2：我接受对方邀请成为的朋友；0：两种都有
     * @param int $friendStatus 朋友关系的状态
     * @return array|null
     */
    public static function getMyFriends($vipid = 0, $includeSelf = 0, $makeFriedsType = 0, $friendStatus = 10)
    {
        $mate = new ModelMate('vip_friends');
        $condition = null;

        switch ($makeFriedsType) {
            case 1:
                $condition['a_vipid'] = $vipid;
                break;
            case 2:
                $condition['b_vipid'] = $vipid;
                break;
            case 0:
            default:
                $condition['a_vipid'] = $vipid;
                $condition['b_vipid'] = $vipid;
                $condition['_logic'] = "OR";
        }


        $map['_complex'] = $condition;
        $map['friendstatus'] = $friendStatus;

        //dump($map);
        $records = $mate->select($map);

        $friends = null;
        foreach ($records as $record) {
            $friends[] = $record['a_vipid'];
            $friends[] = $record['b_vipid'];
        }

        $friends = array_unique($friends);

        if ($includeSelf == 0) {
            $key = array_search($vipid, $friends);

            if ($key !== false) {
                array_splice($friends, $key, 1);
            }
        }

        return $friends;
    }
}