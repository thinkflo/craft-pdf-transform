<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform;

use bymayo\pdftransform\services\PdfTransformService as PdfTransformServiceService;
use bymayo\pdftransform\variables\PdfTransformVariable;
use bymayo\pdftransform\models\Settings;
use bymayo\pdftransform\gql\directives\PdfToImage;

use Craft;
use craft\base\Plugin;
use craft\services\Gql;
use craft\services\Plugins;
use craft\services\Elements;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use craft\events\RegisterGqlDirectivesEvent;
use yii\base\Event;
use yii\log\FileTarget;

/**
 * Class PdfTransform
 *
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 *
 * @property  PdfTransformServiceService $pdfTransformService
 */
class PdfTransform extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var PdfTransform
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public static function log($message)
   {
      Craft::getLogger()->log($message, \yii\log\Logger::LEVEL_INFO, 'pdf-transform');
   }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'pdfTransformServices' => pdfTransformService::class,
        ]);

        $fileTarget = new FileTarget(
            [
              'logFile' => Craft::getAlias('@storage/logs/pdfTransform.log'),
             'categories' => ['pdf-transform']
            ]
        );
 
        Craft::getLogger()->dispatcher->targets[] = $fileTarget;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('pdfTransform', PdfTransformVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_DIRECTIVES,
            function(RegisterGqlDirectivesEvent $event) {
                $event->directives[] = PdfToImage::class;
            }
        );

        Craft::info(
            Craft::t(
                'pdf-transform',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function(Event $event)   {

                $element = $event->element;

               if ($element instanceof \craft\elements\Asset) {
                  if ($event->isNew && $element->extension === 'pdf') {
                    // @TODO: trigger the imageToPdf function
                    PdfTransform::$plugin->pdfTransformService->pdfToImage($element);
                 }
               }

            }
        );

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {

        return Craft::$app->view->renderTemplate(
            'pdf-transform/settings',
            [
                'settings' => $this->getSettings(),
                'volumes' => PdfTransform::$plugin->pdfTransformService->getVolumeOptions()
            ]
        );

    }
}
