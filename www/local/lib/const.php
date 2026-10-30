<?php

final class K
{
    const SITE_INFO_HL_CODE = 'Siteinfo';
    const CATEGORY_HL_ID = 3;

    const KONTAKTY_IBLOCK_ID = 0;

    const ADMINISTRACION_CONTACT_ELEMENT_ID = 0;

    const STATIC_PAGES_IBLOCK_ID = 1;

    const USTAV_POSELENIYA_IBLOCK_ID = 36;

    const BANNERY_IBLOCK_ID = 8;

    const INFORMATSIONNYE_SSYLKI_IBLOCK_ID = 10;

    const MAIN_SLIDER_IBLOCK_ID = 11;

	const NOVOSTI_IBLOCK_ID = 12;

    public static function address()
    {
        return __t('ADDRESS');
    }

    public static function fullNameAdministration()
    {
        return __t('FULL_NAME_ADMINISTRATION');
    }

    public static function logo()
    {
        return App\Content::info('LOGO')['FILE']['src'];
    }

    public static function population()
    {
        return App\Content::info('POPULATION')['UF_VALUE'];
    }

    public static function year()
    {
        return App\Content::info('YEAR')['UF_VALUE'];
    }

    public static function social() 
    {
        return App\Content::social();
    }

	public static function ustavInSidebar($code = '')
	{
		$ustavList = App\Content::getUstav(iblockId: K::USTAV_POSELENIYA_IBLOCK_ID, propId: 13);
		if ($code && isset($ustavList[$code])) {
			return $ustavList[$code];
		}	
		return $ustavList;
	}
}