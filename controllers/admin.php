<?php

class SPODPUBLIC_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function settings($params)
    {
        $this->setPageTitle(OW::getLanguage()->text('spodpublic', 'admin_title'));
        $this->setPageHeading(OW::getLanguage()->text('spodpublic', 'admin_heading'));

        $this->assign('publicRoom', SPODPUBLIC_BOL_Service::getInstance()->getAgora());

        $deleteUrl = OW::getRouter()->urlFor(__CLASS__, 'delete');
        $this->assign('deleteUrl', $deleteUrl);
    }

    public function delete( $params )
    {
        if ( isset($_REQUEST['id']))
        {
            SPODPUBLIC_BOL_Service::getInstance()->removeRoom($_REQUEST['id']);
        }

        $this->redirect(OW::getRouter()->urlForRoute('public-room-settings'));
    }
}