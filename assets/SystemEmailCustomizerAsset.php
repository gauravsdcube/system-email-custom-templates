<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\assets;

use humhub\components\assets\AssetBundle;

class SystemEmailCustomizerAsset extends AssetBundle
{
    public $sourcePath = '@system-email-customizer/resources';

    public $css = ['css/admin.css'];
    public $js = ['js/admin.js'];

    public $depends = [
        'humhub\assets\AppAsset',
    ];
}
