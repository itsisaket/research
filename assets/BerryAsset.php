<?php
namespace app\assets;

use yii\web\AssetBundle;

class BerryAsset extends AssetBundle
{
    public $basePath = '@webroot';   // ชี้ไป web/
    public $baseUrl  = '@web';

    public $css = [
        'template/berry/fonts/phosphor/duotone/style.css',
        'template/berry/fonts/tabler-icons.min.css',
        'template/berry/fonts/feather.css',
        'template/berry/fonts/fontawesome.css',
        'template/berry/fonts/material.css',
        'template/berry/css/style-preset.css',
        'template/berry/css/style.css',
        'template/berry/css/custom.css',
    ];
    public $js = [
        'template/berry/js/plugins/popper.min.js',
        'template/berry/js/plugins/bootstrap.min.js',
        'template/berry/js/plugins/simplebar.min.js',
        'template/berry/js/plugins/apexcharts.min.js',
        'template/berry/js/plugins/feather.min.js',
        'template/berry/js/fonts/custom-font.js',
        'template/berry/js/script.js',
        'template/berry/js/theme.js',
    ];


    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public $depends = [
        'yii\web\YiiAsset',
        // Uncomment if you use Yii Bootstrap 5 styles
        // 'yii\bootstrap5\BootstrapAsset',
    ];
}

