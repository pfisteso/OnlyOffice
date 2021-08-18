<?php

use ILIAS\Filesystem\Exception\IOException;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Location;
use srag\Plugins\OnlyOffice\ObjectSettings\ObjectSettingsFormGUI;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileRepository;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileVersionRepository;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileChangeRepository;
use srag\Plugins\OnlyOffice\StorageService\StorageService;
use srag\Plugins\OnlyOffice\Utils\OnlyOfficeTrait;
use srag\DIC\OnlyOffice\DICTrait;
use srag\Plugins\OnlyOffice\StorageService\InfoService;

/**
 * Class ilObjOnlyOfficeGUI
 * Generated by SrPluginGenerator v1.3.4
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @ilCtrl_isCalledBy ilObjOnlyOfficeGUI: ilRepositoryGUI
 * @ilCtrl_isCalledBy ilObjOnlyOfficeGUI: ilObjPluginDispatchGUI
 * @ilCtrl_isCalledBy ilObjOnlyOfficeGUI: ilAdministrationGUI
 * @ilCtrl_Calls      ilObjOnlyOfficeGUI: ilPermissionGUI
 * @ilCtrl_Calls      ilObjOnlyOfficeGUI: ilInfoScreenGUI
 * @ilCtrl_Calls      ilObjOnlyOfficeGUI: ilObjectCopyGUI
 * @ilCtrl_Calls      ilObjOnlyOfficeGUI: ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjOnlyOfficeGUI: xonoContentGUI
 */
class ilObjOnlyOfficeGUI extends ilObjectPluginGUI
{

    use DICTrait;
    use OnlyOfficeTrait;

    const PLUGIN_CLASS_NAME = ilOnlyOfficePlugin::class;
    const CMD_MANAGE_CONTENTS = "manageContents";
    const CMD_PERMISSIONS = "perm";
    const CMD_SETTINGS = "settings";
    const CMD_SETTINGS_STORE = "settingsStore";
    const CMD_SHOW_CONTENTS = "showContents";
    const CMD_SHOW_VERSIONS = "showVersions";
    const CMD_SAVE = 'save';
    const CMD_CANCEL = 'cancel';
    const LANG_MODULE_OBJECT = "object";
    const LANG_MODULE_SETTINGS = "settings";
    const TAB_CONTENTS = "contents";
    const TAB_PERMISSIONS = "perm_settings";
    const TAB_SETTINGS = "settings";

    const TAB_SHOW_CONTENTS = "show_contents";
    const POST_VAR_FILE = 'upload_files';
    const POST_VAR_OPEN_SETTING = 'open_setting';
    /**
     * @var ilObjOnlyOffice
     */
    public $object;
    /**
     * @var StorageService
     */
    protected $storage_service;
    /**
     * @var ilOnlyOfficePlugin
     */
    protected $plugin;

    /**
     * @inheritDoc
     */
    protected function afterConstructor()/*: void*/
    {
        $this->storage_service = new StorageService(
            self::dic()->dic(),
            new ilDBFileVersionRepository(),
            new ilDBFileRepository(),
            new ilDBFileChangeRepository()
        );
    }

    /**
     * @inheritDoc
     */
    public final function getType() : string
    {
        return ilOnlyOfficePlugin::PLUGIN_ID;
    }

    /**
     * @param string $cmd
     * @throws ilCtrlException
     */
    public function performCommand(string $cmd)/*: void*/
    {
        self::dic()->help()->setScreenIdComponent(ilOnlyOfficePlugin::PLUGIN_ID);
        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            case strtolower(xonoContentGUI::class):
                $xonoContentGUI = new xonoContentGUI(self::dic()->dic(), $this->plugin, $this->object_id);
                self::dic()->ctrl()->forwardCommand($xonoContentGUI);
                break;
            case strtolower(xonoEditorGUI::class):
                $xonoEditorGUI = new xonoEditorGUI(self::dic()->dic(), $this->plugin, $this->obj_id);
                self::dic()->ctrl()->forwardCommand($xonoEditorGUI);
                break;
            default:
                switch ($cmd) {
                    case self::CMD_SHOW_CONTENTS:
                        // Read commands
                        if (!ilObjOnlyOfficeAccess::hasReadAccess()) {
                            ilObjOnlyOfficeAccess::redirectNonAccess(ilRepositoryGUI::class);
                        }
                        $file_info = new InfoService(self::dic()->dic());
                        $open_setting = $file_info->getOpenSetting($this->obj_id);
                        switch ($open_setting) {
                            case "download":
                                $next_cmd = xonoContentGUI::CMD_DOWNLOAD;
                                break;
                            case "editor":
                                $next_cmd = xonoContentGUI::CMD_EDIT;
                                break;
                            default:
                                $next_cmd = xonoContentGUI::CMD_SHOW_VERSIONS;
                        }

                        self::dic()->ctrl()->redirectByClass(xonoContentGUI::class, $next_cmd);
                        break;

                    case self::CMD_SHOW_VERSIONS:
                        self::dic()->ctrl()->redirectByClass(xonoContentGUI::class, xonoContentGUI::CMD_SHOW_VERSIONS);
                        break;

                    case self::CMD_MANAGE_CONTENTS:
                        self::dic()->ctrl()->redirectByClass(xonoEditorGUI::class, xonoEditorGUI::CMD_EDIT);
                        break;

                    case self::CMD_SETTINGS:
                    case self::CMD_SETTINGS_STORE:
                        // Write commands
                        if (!ilObjOnlyOfficeAccess::hasWriteAccess()) {
                            ilObjOnlyOfficeAccess::redirectNonAccess($this);
                        }

                        $this->{$cmd}();
                        break;

                    default:
                        // Unknown command
                        ilObjOnlyOfficeAccess::redirectNonAccess(ilRepositoryGUI::class);
                        break;
                }
                break;
        }
    }

    /**
     * @param string $html
     */
    protected function show(string $html)/*: void*/
    {
        if (!self::dic()->ctrl()->isAsynch()) {
            self::dic()->ui()->mainTemplate()->setTitle($this->object->getTitle());

            self::dic()->ui()->mainTemplate()->setDescription($this->object->getDescription());

            if (!$this->object->isOnline()) {
                self::dic()->ui()->mainTemplate()->setAlertProperties([
                    [
                        "alert" => true,
                        "property" => self::plugin()->translate("status", self::LANG_MODULE_OBJECT),
                        "value" => self::plugin()->translate("offline", self::LANG_MODULE_OBJECT)
                    ]
                ]);
            }
        }

        self::output()->output($html);
    }

    /**
     * @inheritDoc
     */
    public function initCreateForm(/*string*/ $a_new_type) : ilPropertyFormGUI
    {
        $form = parent::initCreateForm($a_new_type);
        $file_input = new ilFileInputGUI($this->txt('form_input_file'), self::POST_VAR_FILE);
        $file_input->setRequired(true);
        $form->addItem($file_input);
        $opening_setting = new ilRadioGroupInputGUI($this->plugin->txt("form_open_setting"),
            self::POST_VAR_OPEN_SETTING);
        // ToDo: Can I set a default value?
        $opening_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_editor"), "editor"));
        $opening_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_download"), "download"));
        $opening_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_ilias"), "ilias"));
        $opening_setting->setRequired(true);
        $form->addItem($opening_setting);

        return $form;
    }

    /**
     * @inheritDoc
     * @param ilObject $a_new_object
     * @throws IllegalStateException
     * @throws IOException
     * @throws ilDateTimeException
     */
    public function afterSave(/*ilObjOnlyOffice*/ ilObject $a_new_object)/*: void*/
    {
        $form = $this->initCreateForm($a_new_object->getType());
        $form->checkInput();

        //ToDo: OpenSetting as ObjectSetting?
        self::dic()->upload()->process();
        $results = self::dic()->upload()->getResults();
        $result = end($results);
        $this->storage_service->createNewFileFromUpload($result, $a_new_object->getId());
        parent::afterSave($a_new_object);
    }

    /**
     * @return ObjectSettingsFormGUI
     */
    protected function getSettingsForm() : ObjectSettingsFormGUI
    {
        $form = new ObjectSettingsFormGUI($this, $this->object);
        $open_setting = new ilRadioGroupInputGUI($this->plugin->txt("form_open_setting"),
            self::POST_VAR_OPEN_SETTING);
        // ToDo: Can I set a default value?
        $open_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_editor"), "editor"));
        $open_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_download"), "download"));
        $open_setting->addOption(new ilRadioOption($this->plugin->txt("form_open_ilias"), "ilias"));
        $open_setting->setRequired(true);
        $form->addItem($open_setting);

        return $form;
    }

    /**
     *
     */
    protected function settings()/*: void*/
    {
        self::dic()->logger()->root()->info("Settings Post");
        self::dic()->tabs()->activateTab(self::TAB_SETTINGS);

        $form = $this->getSettingsForm();

        self::output()->output($form);
    }

    /**
     *
     */
    protected function settingsStore()/*: void*/
    {
        self::dic()->tabs()->activateTab(self::TAB_SETTINGS);

        $form = $this->getSettingsForm();

        if (!$form->storeForm()) {
            self::output()->output($form);

            return;
        }
        $new_open_setting = $_POST[self::POST_VAR_OPEN_SETTING];
        $this->storage_service->updateOpenSetting($this->obj_id, $new_open_setting);

        ilUtil::sendSuccess(self::plugin()->translate("saved", self::LANG_MODULE_SETTINGS), true);

        self::dic()->ctrl()->redirect($this, self::CMD_SETTINGS);
    }

    /**
     *
     */
    protected function setTabs()/*: void*/
    {
        self::dic()->tabs()->addTab(self::TAB_SHOW_CONTENTS,
            self::plugin()->translate("show_contents", self::LANG_MODULE_OBJECT), self::dic()->ctrl()
                                                                                      ->getLinkTarget($this,
                                                                                          self::CMD_SHOW_VERSIONS));

        if (ilObjOnlyOfficeAccess::hasWriteAccess()) {
            self::dic()->tabs()->addTab(self::TAB_SETTINGS,
                self::plugin()->translate("settings", self::LANG_MODULE_SETTINGS), self::dic()->ctrl()
                                                                                       ->getLinkTarget($this,
                                                                                           self::CMD_SETTINGS));
        }

        if (ilObjOnlyOfficeAccess::hasEditPermissionAccess()) {
            self::dic()->tabs()->addTab(self::TAB_PERMISSIONS,
                self::plugin()->translate(self::TAB_PERMISSIONS, "", [], false), self::dic()->ctrl()
                                                                                     ->getLinkTargetByClass([
                                                                                         self::class,
                                                                                         ilPermissionGUI::class
                                                                                     ], self::CMD_PERMISSIONS));
        }

        self::dic()->tabs()->manual_activation = true; // Show all tabs as links when no activation
    }

    /**
     * @return string
     */
    public static function getStartCmd() : string
    {
        if (ilObjOnlyOfficeAccess::hasWriteAccess()) {
            return self::CMD_MANAGE_CONTENTS;
        } else {
            return self::CMD_SHOW_CONTENTS;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAfterCreationCmd() : string
    {
        return self::getStartCmd();
    }

    /**
     * @inheritDoc
     */
    public function getStandardCmd() : string
    {
        return self::getStartCmd();
    }

}
