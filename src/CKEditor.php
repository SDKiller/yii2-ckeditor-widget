<?php
/**
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace dosamigos\ckeditor;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * CKEditor renders a CKEditor js plugin for classic editing.
 * @see http://docs.ckeditor.com/
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 * @package dosamigos\ckeditor
 */
class CKEditor extends InputWidget
{
    use CKEditorTrait;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initOptions();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
        $this->registerPlugin();
    }

    /**
     * Registers CKEditor plugin
     * @codeCoverageIgnore
     */
    protected function registerPlugin()
    {
        $js = [];

        $view = $this->getView();

        CKEditorWidgetAsset::register($view);

        $id = $this->options['id'];

        $options = $this->clientOptions !== false && !empty($this->clientOptions)
            ? Json::encode($this->clientOptions)
            : '{}';

        $js[] = "CKEDITOR.replace('$id', $options);";
        $js[] = "dosamigos.ckEditorWidget.registerOnChangeHandler('$id');";

        if (isset($this->clientOptions['filebrowserUploadUrl'])) {
            $js[] = "dosamigos.ckEditorWidget.registerCsrfImageUploadHandler();";
        }

        //@see http://docs.ckeditor.com/#!/guide/dev_file_upload-section-basic-configuration
        //@see http://docs.ckeditor.com/#!/guide/dev_file_upload-section-editor-side-configuration
        if (isset($this->clientOptions['uploadUrl']) || isset($this->clientOptions['imageUploadUrl'])) {
            /* @var $request \yii\web\Request */
            $request = \Yii::$app->request;
            $js[] = 'CKEDITOR && CKEDITOR.instances[\'' . $id . '\'] && CKEDITOR.instances[\'' . $id . '\'].on( \'fileUploadRequest\', function(evt) {
                        var xhr = evt.data.fileLoader.xhr;
                        xhr.setRequestHeader( \'' . $request::CSRF_HEADER . '\', \'' . $request->getCsrfToken() . '\' );
                    } );';
        }

        $view->registerJs(implode("\n", $js));
    }
}
