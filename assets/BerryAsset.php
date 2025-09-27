<?php
namespace app\assets;

use yii\web\AssetBundle;

class BerryAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $sourcePath = '@app/template/berry'; // โฟลเดอร์จริงของคุณ

    public $css = [
        'fonts/phosphor/duotone/style.css',
        'fonts/tabler-icons.min.css',
        'fonts/feather.css',
        'fonts/fontawesome.css',
        'fonts/material.css',
        'css/style-preset.css',
        'css/style.css',
        'css/custom.css',
    ];
    public $js = [
        'js/plugins/popper.min.js',
        'js/plugins/bootstrap.min.js',
        'js/plugins/simplebar.min.js',
        'js/plugins/apexcharts.min.js',
        'js/plugins/feather.min.js',
        'js/icon/custom-font.js',
        'js/script.js',
        'js/theme.js',
    ];


    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public $depends = [
        'yii\web\YiiAsset',
        // Uncomment if you use Yii Bootstrap 5 styles
        // 'yii\bootstrap5\BootstrapAsset',
    ];
}

