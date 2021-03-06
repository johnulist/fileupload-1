<?php
/**
 * 2015 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 * @author    Michael Dekker <prestashop@michaeldekker.com>
 * @copyright 2015 Michael Dekker
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FileUpload extends Module
{
    public function __construct()
    {
        $this->name = 'fileupload';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Michael Dekker';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('File upload');
        $this->description = $this->l('Allows your customers to upload files');

        // Checksums of original files
        $this->md5sums = array(
            '4e777e6b21b64250627579c8928bcb6d',
            '50d48f07275ffd37680417b313390823',
            'ddd07f1a716a1a9ba5ba7df5ff2f6ebe',
            '4b08e7271129c7c8b06697909d6a2b19',
            '418f209271694e04eaaccf7be84638e0',
            '1d7aeb7dfb45cb00dc60b5bd3d935e4b',
            '63204232fea87a6b026166a734b4970a',
            '7982d865c3c95e38b8c3b53c031c24f7',
            'e2188c2d7b742b6d164b3baddad2357e',
            '7982d865c3c95e38b8c3b53c031c24f7',
            'd9697235a0e15aaf503bb2ed8787ade5',
            'c2dfd830d725422d37080db66b30644e',
            '0dd9a24e32016dcd692d0f5d155d3222',
            '8a9ce15c0db5f791d2f10b45d61c0d51',
            'c67783e491e97c671aacafa78cb403ed',
            '54a1b00dc1d3ffafdf3060638a507074',
            '7e8f09de8d336c8e52bbde3a88e907c0'
        );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (is_file(_PS_MODULE_DIR_.'fileupload/copy/displayImage.php')) {
            if (in_array(md5(Tools::file_get_contents(_PS_ADMIN_DIR_.'/displayImage.php')), $this->md5sums)) {
                rename(_PS_ADMIN_DIR_.'/displayImage.php', _PS_MODULE_DIR_.'fileupload/backup/displayImage.php');
            }
            unlink(_PS_ADMIN_DIR_.'/displayImage.php');
            copy(_PS_MODULE_DIR_.'fileupload/copy/displayImage.php', _PS_ADMIN_DIR_.'/displayImage.php');
        } else {
            return false;
        }
        Configuration::updateValue('FILEUPLOAD_FILE_EXTS', serialize(array('jpg','png','gif')));
        return parent::install();
    }

    public function uninstall()
    {
        if (is_file(_PS_MODULE_DIR_.'fileupload/backup/displayImage.php') &&
            !in_array(md5(Tools::file_get_contents(_PS_ADMIN_DIR_.'/displayImage.php')), $this->md5sums)) {
            unlink(_PS_ADMIN_DIR_.'/displayImage.php');
            rename(_PS_MODULE_DIR_.'fileupload/backup/displayImage.php', _PS_ADMIN_DIR_.'/displayImage.php');
        }
        Configuration::deleteByName('FILEUPLOAD_FILE_EXTS');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        $lang_id = $this->context->language->id;

        $override_error = $this->l('Overrides are disabled. This modules doesn\'t work without overrides. Go to').' "'.
            Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'tab_lang` WHERE id_tab = 17 AND id_lang ='.pSQL($lang_id)).
            ' > '.
            Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'tab_lang` WHERE id_tab = 77 AND id_lang ='.pSQL($lang_id)).
            '" '.$this->l('and make sure that the option').' "'.
            Translate::getAdminTranslation('Disable all overrides', 'AdminPerformance').
            '" '.$this->l('is set to').' "'.
            Translate::getAdminTranslation('No', 'AdminPerformance').
            '"'.$this->l('.');

        if (Configuration::get('PS_DISABLE_OVERRIDES') == '1') {
            $output .= $this->displayError($override_error);
        }

        if (Tools::isSubmit('submit'.$this->name)) {
            // Check if string is correct
            if (preg_match("/^[A-Za-z0-9,]*$/", Tools::getValue('FILEUPLOAD_FILE_EXTS'))) {
                Configuration::updateValue(
                    'FILEUPLOAD_FILE_EXTS',
                    serialize(explode(',', Tools::getValue('FILEUPLOAD_FILE_EXTS')))
                );
                $output .= $this->displayConfirmation(Translate::getAdminTranslation('Update successful', 'AdminAccess'));
            } else {
                $output .= $this->displayError($this->l('Incorrect file types'));
            }
        }

        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => Translate::getAdminTranslation('Settings', 'AdminReferrers'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Allowed file extensions'),
                    'name' => 'FILEUPLOAD_FILE_EXTS',
                    'size' => 200,
                    'required' => false,
                    'desc' => $this->l('Choose the allowed file extensions. Please use a comma to separate them (e.g. gif,jpg,psd,zip). Leave empty to disable file extension check.')
                )
            ),
            'submit' => array(
                'title' => Translate::getAdminTranslation('Save', 'AdminReferrers'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => Translate::getAdminTranslation('Save', 'AdminReferrers'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.
                    Tools::getAdminTokenLite('AdminModules'),
                    'desc' => Translate::getAdminTranslation('Back to list', 'AdminAttributesGroups')
                )
        );

        // Load current value
        $exts = Configuration::get('FILEUPLOAD_FILE_EXTS');
        if ($exts) {
            $helper->fields_value['FILEUPLOAD_FILE_EXTS'] = implode(
                ',',
                unserialize(Configuration::get('FILEUPLOAD_FILE_EXTS'))
            );
        } else {
            $helper->fields_value['FILEUPLOAD_FILE_EXTS'] = '';
        }
        return $output.$helper->generateForm($fields_form).$this->displayExtra();
    }

    protected function displayExtra()
    {
        return $this->display(__FILE__, 'views/templates/admin/extra.tpl');
    }
}
