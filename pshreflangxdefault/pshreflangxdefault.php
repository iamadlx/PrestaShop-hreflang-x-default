<?php
/**
 * PrestaShop Hreflang x-default Selector
 *
 * Adds a hreflang="x-default" link tag in the <head>, pointing to a
 * user-selected language URL. Injected through displayHeader, no theme edits.
 *
 * Note: the configuration keys are spelled PSHRELANGXDEFAULT_* (missing F).
 * Do NOT rename them: they are stored in the database of existing installs,
 * renaming would orphan the merchant's settings.
 *
 * Author : ADLX (https://github.com/iamadlx)
 * License: MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pshreflangxdefault extends Module
{
    const CONF_ENABLED = 'PSHRELANGXDEFAULT_ENABLED';
    const CONF_LANG_ID = 'PSHRELANGXDEFAULT_LANG_ID';

    public function __construct()
    {
        $this->name = 'pshreflangxdefault';
        $this->tab = 'seo';
        $this->version = '1.0.2';
        $this->author = 'ADLX';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '1.7.8.0',
            'max' => '8.99.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Hreflang x-default Selector');
        $this->description = $this->l('Tells Google which language of your shop to show to visitors whose language you do not offer (x-default tag), without editing your theme.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? Its settings will be deleted.');
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
                $output .= $this->displayError($this->l('Please select a valid active language.'));
            } else {
                Configuration::updateValue(self::CONF_ENABLED, $enabled ? 1 : 0);
                Configuration::updateValue(self::CONF_LANG_ID, $idLang);
                $output .= $this->displayConfirmation($this->l('Settings updated.'));
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
            'name' => $this->l('-- Select a language --'),
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
                    'title' => $this->l('Default language for search engines'),
                    'icon'  => 'icon-globe',
                ],
                'description' => $this->l('Tells Google which version of your pages to show to visitors whose language does not exist on your shop. Usually: English. Your visitors see no difference, it is an invisible signal for search engines.'),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable'),
                        'name' => self::CONF_ENABLED,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                        'desc' => $this->l('When disabled, nothing is added to your pages.'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Language for other visitors'),
                        'name' => self::CONF_LANG_ID,
                        'required' => true,
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                        'desc' => $this->l('Pick one of the languages of your shop. In most cases, English.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
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
                (int)Configuration::get(self::CONF_ENABLED)
            ),
            self::CONF_LANG_ID => (int)Tools::getValue(
                self::CONF_LANG_ID,
                (int)Configuration::get(self::CONF_LANG_ID)
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

        return $this->display(__FILE__, 'xdefault.tpl');
    }
}
