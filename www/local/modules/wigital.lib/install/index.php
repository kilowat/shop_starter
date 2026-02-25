<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class wigital_lib extends \CModule
{
	public function __construct()
	{
		$this->MODULE_ID = 'wigital.lib';
		$this->MODULE_NAME = Loc::getMessage('WIGITAL_LIB_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('WIGITAL_LIB_DESCRIPTION');

		require __DIR__ . '/version.php';

		if (isset($arModuleVersion['VERSION']))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		}

		if (isset($arModuleVersion['VERSION_DATE']))
		{
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
	}

	public function DoInstall()
	{
		global $USER;

		/**
		 * @var \CUser $USER
		 */

		if (!$USER->IsAdmin())
		{
			return;
		}

		ModuleManager::registerModule($this->MODULE_ID);

		$this->InstallDB();
		$this->InstallFiles();
		$this->InstallEvents();
		$this->InstallTasks();
	}

	public function DoUninstall()
	{
		global $USER;

		/**
		 * @var \CUser $USER
		 */

		if (!$USER->IsAdmin())
		{
			return;
		}

		$this->UnInstallDB();
		$this->UnInstallTasks();
		$this->UnInstallEvents();
		$this->UnInstallFiles();

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function InstallDB()
	{
		// установка событий
		// EventManager::getInstance()->registerEventHandler(...);

		// установка агентов
		// CAgent::AddAgent(...);
	}

	public function InstallEvents()
	{
		// установка почтовых и СМС шаблонов
		// $type = new CEventType;
		// $type->Add(...);
	}

	public function InstallFiles()
	{
		// установка файлов
		// CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . "/local/modules/{$this->MODULE_ID}/install/admin", $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
	}
}