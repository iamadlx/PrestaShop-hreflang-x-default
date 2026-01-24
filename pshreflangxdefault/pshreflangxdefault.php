<?php
/**
 * PrestaShop Hreflang x-default Selector
 *
 * Adds a hreflang="x-default" link tag in the <head>, pointing to a user-selected language URL.
 *
 * @author ADLX
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Pshreflangxdefault extends Module
{
    const CONF_ENABLED = 'PSHRELANGXDEFAULT_ENABLED';
    const CONF_LANG_ID = 'PSHRELANGXDEFAULT_LANG_ID';

    /**
     * Minimal BO bilingual helper (FR/EN) based on the Back Office language.
     * Falls back to English for any other BO language.
     */
    protected function tr($en, $fr)
    {
        $iso = isset($this->context->language->iso_code) ? strtolower((string) $this->context->language->iso_code) : 'en';
        return ($iso === 'fr') ? $fr : $en;
    }

    public function __construct()
    {
        $this->name = 'pshreflangxdefault';
        $this->tab = 'seo';
        $this->version = '1.0.0';
        $this->author = 'ADLX';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('PrestaShop Hreflang x-default Selector');
        $this->description = $this->l('Adds a hreflang="x-default" tag and lets you pick which language URL it should point to.');
        $this->ps_versions_compliancy = [
            'min' => '1.7.8.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && Configuration::updateValue(self::CONF_ENABLED, 0)
            && Configuration::updateValue(self::CONF_LANG_ID, 0);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName(self::CONF_ENABLED)
            && Configuration::deleteByName(self::CONF_LANG_ID);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $enabled = (int)Tools::getValue(self::CONF_ENABLED);
            $idLang = (int)Tools::getValue(self::CONF_LANG_ID);

            $activeLangIds = array_map(function ($l) {
                return (int)$l['id_lang'];
            }, Language::getLanguages(true));

            // Force an explicit selection: no implicit shop default.
            if ($idLang <= 0 || !in_array($idLang, $activeLangIds, true)) {
                $output .= $this->displayError($this->tr('Please select a valid active language.', 'Veuillez sélectionner une langue active valide.'));
            } else {
                Configuration::updateValue(self::CONF_ENABLED, $enabled ? 1 : 0);
                Configuration::updateValue(self::CONF_LANG_ID, $idLang);
                $output .= $this->displayConfirmation($this->tr('Settings updated.', 'Paramètres enregistrés.'));
            }
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $languages = Language::getLanguages(true);

        $options = [];
        // Placeholder (forces an explicit selection)
        $options[] = [
            'id_option' => 0,
            'name' => $this->tr('— Select a language —', '— Sélectionner une langue —'),
        ];
        foreach ($languages as $lang) {
            $label = $lang['name'];
            if (!empty($lang['iso_code'])) {
                $label .= ' (' . $lang['iso_code'] . ')';
            }
            $options[] = [
                'id_option' => (int)$lang['id_lang'],
                'name' => $label,
            ];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->tr('x-default configuration', 'Configuration x-default'),
                    'icon'  => 'icon-globe',
                ],
                'description' => $this->tr(
                    'The <strong>x-default</strong> hreflang is a fallback URL used when the user language/region does not match any of your alternates. This module injects <code>&lt;link rel="alternate" hreflang="x-default" ...&gt;</code> into the <code>&lt;head&gt;</code> via <code>displayHeader</code> — no theme edits.',
                    'Le hreflang <strong>x-default</strong> est une URL de repli quand la langue/région de l’utilisateur ne correspond à aucune alternative. Ce module injecte <code>&lt;link rel="alternate" hreflang="x-default" ...&gt;</code> dans le <code>&lt;head&gt;</code> via <code>displayHeader</code> — sans modifier le thème.'
                ),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->tr('Enable injection', 'Activer l’injection'),
                        'name' => self::CONF_ENABLED,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'enabled_on',
                                'value' => 1,
                                'label' => $this->tr('Enabled', 'Activé'),
                            ],
                            [
                                'id' => 'enabled_off',
                                'value' => 0,
                                'label' => $this->tr('Disabled', 'Désactivé'),
                            ],
                        ],
                        'desc' => $this->tr('When disabled, nothing is added to your pages.', 'Quand désactivé, rien n’est ajouté à vos pages.'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->tr('x-default URL language', 'Langue de l’URL x-default'),
                        'name' => self::CONF_LANG_ID,
                        'required' => true,
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                        'desc' => $this->tr(
                            'Pick one of your <strong>active</strong> shop languages. That language URL will be used for <code>hreflang="x-default"</code>.',
                            'Choisissez une des langues <strong>actives</strong> de la boutique. L’URL de cette langue sera utilisée pour <code>hreflang="x-default"</code>.'
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->tr('Save', 'Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)$this->context->language->id;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int)$this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    protected function getConfigFieldsValues()
    {
        return [
            self::CONF_ENABLED => (int)Tools::getValue(
                self::CONF_ENABLED,
                (int)Configuration::get(self::CONF_ENABLED, 0)
            ),
            self::CONF_LANG_ID => (int)Tools::getValue(
                self::CONF_LANG_ID,
                (int)Configuration::get(self::CONF_LANG_ID, 0)
            ),
        ];
    }

    public function hookDisplayHeader($params)
    {
        $enabled = (int)Configuration::get(self::CONF_ENABLED);
        if (!$enabled) {
            return '';
        }

        $idLangDefault = (int)Configuration::get(self::CONF_LANG_ID);
        if ($idLangDefault <= 0) {
            return '';
        }

        // If theme already outputs x-default, don't duplicate.
        $urls = $this->context->smarty->getTemplateVars('urls');
        if (is_array($urls) && isset($urls['alternative_langs']) && is_array($urls['alternative_langs'])) {
            if (isset($urls['alternative_langs']['x-default'])) {
                return '';
            }
        }

        $xdefaultUrl = null;

        // Prefer the URL computed by PrestaShop ($urls.alternative_langs) when available
        if (is_array($urls) && isset($urls['alternative_langs']) && is_array($urls['alternative_langs'])) {
            $langs = Language::getLanguages(true);
            $target = null;

            foreach ($langs as $lang) {
                if ((int)$lang['id_lang'] === $idLangDefault) {
                    $target = $lang;
                    break;
                }
            }

            if ($target) {
                $candidateCodes = [];

                // Typical keys from core are often like "en-us" or "fr"
                if (!empty($target['language_code'])) {
                    $candidateCodes[] = strtolower((string)$target['language_code']);
                }
                if (!empty($target['locale'])) {
                    $candidateCodes[] = strtolower(str_replace('_', '-', (string)$target['locale']));
                }
                if (!empty($target['iso_code'])) {
                    $candidateCodes[] = strtolower((string)$target['iso_code']);
                }

                // Try to match any candidate code inside alternative_langs
                foreach ($candidateCodes as $code) {
                    if (isset($urls['alternative_langs'][$code])) {
                        $xdefaultUrl = $urls['alternative_langs'][$code];
                        break;
                    }
                }
            }
        }

        // Fallback: ask PrestaShop for the "same page in another language" link
        if (!$xdefaultUrl) {
            try {
                $xdefaultUrl = $this->context->link->getLanguageLink($idLangDefault);
            } catch (Exception $e) {
                // Last resort: home URL in that language
                $xdefaultUrl = $this->context->link->getPageLink('index', true, $idLangDefault);
            }
        }

        if (!$xdefaultUrl) {
            return '';
        }

        $this->context->smarty->assign([
            'pshreflangxdefault_url' => $xdefaultUrl,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/xdefault.tpl');
    }
}
