<?php
/**
 * Created by PhpStorm.
 * User: xiedalie
 * Date: 2016/12/5
 * Time: 15:48
 */

namespace Home\Model;

use Vendor\Hiland\Utils\Data\DateHelper;
use Vendor\Hiland\Utils\Data\GuidHelper;
use Vendor\Hiland\Utils\IO\DirHelper;
use Vendor\Hiland\Utils\IO\ImageHelper;

class ShenqiBiz
{
    public static function mingxingliaotian($name = "解然", $starName = '')
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\mingxingliaotian\\mingxingliaotian.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\simhei.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 255, 255, 255);
        imagefttext($imagemegered, 22, 0, 295, 843, $textcolor, $fontFileName, $name);

        imagefttext($imagemegered, 22, 0, 85, 843, $textcolor, $fontFileName, $starName);
        imagefttext($imagemegered, 22, 0, 85, 710, $textcolor, $fontFileName, $starName);
        imagefttext($imagemegered, 22, 0, 85, 578, $textcolor, $fontFileName, $starName);
        imagefttext($imagemegered, 22, 0, 85, 433, $textcolor, $fontFileName, $starName);


        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 22, 0, 225, 305, $textcolor, $fontFileName, $sinDate);

        $sinTime = DateHelper::format(null, "H:i");
        imagefttext($imagemegered, 88, 0, 200, 255, $textcolor, $fontFileName, $sinTime);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    private static function saveImageAndGetRelativePath($imageResource)
    {
        $relativePath = "\\Upload\\shenqitupian\\" . DateHelper::format(null, 'Y-m-d') . "\\";
        $targetFilePath = PHYSICAL_ROOT_PATH . $relativePath;
        DirHelper::surePathExist($targetFilePath);

        $fileShortName = GuidHelper::newGuid() . ".jpg";
        $relativeFile = $relativePath . $fileShortName;

        $physicalFullName = PHYSICAL_ROOT_PATH . $relativeFile;
        $physicalFullName = str_replace('/', '\\', $physicalFullName);

        $physicalFullName = ImageHelper::save($imageResource, $physicalFullName);
        return $relativeFile;
    }

    public static function ganen($name = "解然", $titleName = '', $content = '')
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\ganen\\main.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\simhei.ttf";
        //$fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 113, 136, 128);
        imagefttext($imagemegered, 22, 0, 465, 933, $textcolor, $fontFileName, $name);

        imagefttext($imagemegered, 22, 0, 115, 743, $textcolor, $fontFileName, $titleName);
        //imagefttext($imagemegered, 22, 0, 85, 760, $textcolor, $fontFileName, $content);

        ImageHelper:: fillText2Image($imagemegered, 22, 0, 165, 780, 430,$textcolor, $fontFileName, $content, 35,60);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function daihe($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\dididaihe\\dididaihe.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\simhei.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 35, 35, 35);
        imagefttext($imagemegered, 18, 5, 160, 495, $textcolor, $fontFileName, $name);
        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function baoye($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\baoye\\meinv.jpg";
        $fingerMarkFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\baoye\\zhiwen.png";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $imageFingerMarker = ImageHelper::loadImage($fingerMarkFileName, 'non');
        //$imageFingerMarker = ImageHelper::resizeImage($imageFingerMarker, 130, 130);
        imagecopy($imagemegered, $imageFingerMarker, 515, 622, 0, 0, imagesx($imageFingerMarker), imagesy($imageFingerMarker));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 35, 0, 480, 670, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y-m-d");
        imagefttext($imagemegered, 20, 0, 410, 690, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }


    public static function zhaokannvhai($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\zhaokannvhai\\main.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 55, 0, 480, 685, $textcolor, $fontFileName, $name);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function neiku($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\neiku\\neiku.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 35, -15, 350, 435, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y m d");
        imagefttext($imagemegered, 20, -10, 220, 445, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function chuanpiao($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\chuanpiao\\chuanpiao.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 35, 0, 250, 315, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(DateHelper::addInterval(time(), "d", 5), "Y m d");
        imagefttext($imagemegered, 20, 0, 220, 435, $textcolor, $fontFileName, $sinDate);

        $sinDate2 = DateHelper::format(null, "Y m d");
        imagefttext($imagemegered, 20, 0, 65, 715, $textcolor, $fontFileName, $sinDate2);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function jiejiu($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\jiejiu\\jiejiu.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 40, 0, 500, 855, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 20, 0, 450, 875, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function wurenji($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\wurenji\\wurenji.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\songti.TTF";
        //$fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 15, -2, 175, 245, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 11, -2, 195, 365, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function maerdaifu($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\maerdaifu\\maerdaifu.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\simhei.ttf";
        //$fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 255, 255, 255);
        imagefttext($imagemegered, 22, 0, 335, 457, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 22, 0, 275, 325, $textcolor, $fontFileName, $sinDate);

        $sinTime = DateHelper::format(null, "H:i");
        imagefttext($imagemegered, 88, 0, 230, 275, $textcolor, $fontFileName, $sinTime);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function nianzhongzongjie($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\nianzhongzongjie\\nianzhongzongjie.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 40, 0, 500, 855, $textcolor, $fontFileName, $name);
        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 20, 0, 450, 875, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }

    public static function xinlingjitang($name = "解然")
    {
        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\xinlingjitang\\xinlingjitang.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\jiangangshouxie.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 85, 85, 85);
        imagefttext($imagemegered, 40, 23, 460, 745, $textcolor, $fontFileName, "赠:" . $name);
        $sinDate = DateHelper::format(null, "Y年m月d日");
        imagefttext($imagemegered, 20, 23, 485, 840, $textcolor, $fontFileName, $sinDate);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }


    public static function hupandaxue($name = "解然")
    {
        $length = mb_strlen($name, 'utf8');
        switch ($length) {
            case 1:
                $name = "  $name  ";
                break;
            case 2:
                $name = mb_substr($name, 0, 1, 'utf8') . "  " . mb_substr($name, 1, 1, 'utf8');
                break;
            default:
                $name = $name;
                break;
        }

        $bgFileName = PHYSICAL_ROOT_PATH . "\\Upload\\shenqi\\hupandaxue\\hupandaxue.jpg";
        $fontFileName = PHYSICAL_ROOT_PATH . "\\Upload\\fonts\\simhei.ttf";

        $imagebg = ImageHelper::loadImage($bgFileName);;
        $imagemegered = imagecreatetruecolor(imagesx($imagebg), imagesy($imagebg));
        imagecopy($imagemegered, $imagebg, 0, 0, 0, 0, imagesx($imagebg), imagesy($imagebg));

        $textcolor = imagecolorallocate($imagemegered, 0, 0, 0);
        imagefttext($imagemegered, 40, 0, 240, 805, $textcolor, $fontFileName, $name);

        $relativeFile = self::saveImageAndGetRelativePath($imagemegered);
        return $relativeFile;
    }


}