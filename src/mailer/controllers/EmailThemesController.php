<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class EmailThemesController extends Controller
{
    public function actionEmailThemesIndexTemplate(): Response
    {
        $themeTypes = MailerModule::getInstance()->emailThemes->getEmailThemeTypes();

        $themes = EmailThemeHelper::getEmailThemes();

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/index.twig', [
            'emailThemes' => $themes,
            'emailThemeTypes' => ComponentHelper::typesToInstances($themeTypes),
        ]);
    }

    public function actionEdit(EmailTheme $emailTheme = null, string $emailThemeUid = null, string $type = null): Response
    {
        $this->requireAdmin();

        if ($emailThemeUid) {
            $emailTheme = EmailThemeHelper::getEmailThemeByUid($emailThemeUid);
        }

        if (!$emailTheme && $type) {
            $emailTheme = new $type();
        }

        $mailers = MailerHelper::getMailers();

        $mailerTypeOptions[] = [
            'label' => Craft::t('sprout-module-mailer', 'Craft Default Mailer'),
            'value' => 'craft',
        ];

        foreach ($mailers as $mailer) {
            $mailerTypeOptions[] = [
                'label' => $mailer->name,
                'value' => $mailer->uid,
            ];
        }

        return $this->renderTemplate('sprout-module-mailer/_settings/email-themes/edit.twig', [
            'emailTheme' => $emailTheme,
            'mailerTypeOptions' => $mailerTypeOptions ?? [],
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $emailTheme = $this->populateEmailThemeModel();

        $emailThemesConfig = EmailThemeHelper::getEmailThemes();
        $emailThemesConfig[$emailTheme->uid] = $emailTheme;

        if (!$emailTheme->validate() || !EmailThemeHelper::saveEmailThemes($emailThemesConfig)) {

            Craft::$app->session->setError(Craft::t('sprout-module-mailer', 'Could not save Email Variant.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'emailTheme' => $emailTheme,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-mailer', 'Email Variant saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (!EmailThemeHelper::reorderEmailThemes($ids)) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('sprout-module-mailer', "Couldn't reorder Email Themes."),
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }

    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $emailThemeUid = Craft::$app->request->getRequiredBodyParam('id');

        $inUse = EmailElement::find()
            ->emailThemeUid($emailThemeUid)
            ->exists();

        if ($inUse || !EmailThemeHelper::removeEmailTheme($emailThemeUid)) {
            return $this->asFailure();
        }

        return $this->asSuccess();
    }

    private function populateEmailThemeModel(): EmailTheme
    {
        $type = Craft::$app->request->getRequiredBodyParam('type');
        $uid = Craft::$app->request->getRequiredBodyParam('uid');

        /** @var EmailTheme $emailTheme */
        $emailTheme = new $type();
        $emailTheme->name = Craft::$app->request->getRequiredBodyParam('name');
        $emailTheme->uid = !empty($uid) ? $uid : StringHelper::UUID();

        if (!$emailTheme::isEditable()) {
            return $emailTheme;
        }

        $emailTheme->displayPreheaderText = Craft::$app->request->getBodyParam('displayPreheaderText');
        $emailTheme->htmlEmailTemplate = Craft::$app->request->getBodyParam('htmlEmailTemplate');
        $emailTheme->textEmailTemplate = Craft::$app->request->getBodyParam('textEmailTemplate');
        $emailTheme->copyPasteEmailTemplate = Craft::$app->request->getBodyParam('copyPasteEmailTemplate');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = EmailElement::class;
        $emailTheme->setFieldLayout($fieldLayout);

        return $emailTheme;
    }
}
