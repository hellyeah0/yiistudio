<?

namespace admin\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * DatePicker asset bundle.
 */
class DatePickerAsset extends AssetBundle {

    public $sourcePath = '@vendor/bower/bootstrap-datepicker';
    public $css = [
        'distr/css/bootstrap-datepicker3.min.css',
    ];
    public $js = [        
        'distr/js/bootstrap-datepicker.min.js',        
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];

    public function init() {
        parent::init();        
        
        if(\Yii::$app->language == 'ru-RU')
        {
            $this->js[] = 'distr/locales/bootstrap-datepicker.ru.min.js';
        }
    }

}
