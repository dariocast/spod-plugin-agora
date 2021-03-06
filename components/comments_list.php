<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class SPODPUBLIC_CMP_CommentsList extends BASE_CMP_CommentsList
{
	protected $actionArr = array('comments' => array(), 'users' => array(), 'abuses' => array(), 'remove_abuses' => array());

	protected function init()
    {
        SPODPUBLIC_CLASS_EventHandler::getInstance()->init();
        
        if ( $this->commentCount === 0 && $this->params->getShowEmptyList() )
        {
            $this->assign('noComments', true);
        }

        $countToLoad = 0;

        if ( $this->commentCount === 0 )
        {
            $commentList = array();
        }
        else if ( in_array($this->params->getDisplayType(), array(BASE_CommentsParams::DISPLAY_TYPE_WITH_LOAD_LIST, BASE_CommentsParams::DISPLAY_TYPE_WITH_LOAD_LIST_MINI)) )
        {	
            $commentList = empty($this->batchData['commentsList']) ? $this->commentService->findCommentList($this->params->getEntityType(), $this->params->getEntityId(), 1, $this->params->getInitialCommentsCount()) : $this->batchData['commentsList'];
            $commentList = array_reverse($commentList);
            $countToLoad = $this->commentCount - $this->params->getInitialCommentsCount();
            $this->assign('countToLoad', $countToLoad);
        }
        else
        {
            $commentList = $this->commentService->findCommentList($this->params->getEntityType(), $this->params->getEntityId(), $this->page, $this->params->getCommentCountOnPage());
        }

        OW::getEventManager()->trigger(new OW_Event('base.comment_list_prepare_data', array('list' => $commentList, 'entityType' => $this->params->getEntityType(), 'entityId' => $this->params->getEntityId())));
        OW::getEventManager()->bind('base.comment_item_process', array($this, 'itemHandler'));
        $this->assign('comments', $this->processList($commentList));

        $pages = false;

        if ( $this->params->getDisplayType() === BASE_CommentsParams::DISPLAY_TYPE_WITH_PAGING )
        {
            $pagesCount = $this->commentService->findCommentPageCount($this->params->getEntityType(), $this->params->getEntityId(), $this->params->getCommentCountOnPage());

            if ( $pagesCount > 1 )
            {
                $pages = $this->getPages($this->page, $pagesCount, 8);
                $this->assign('pages', $pages);
            }
        }
        else
        {
            $pagesCount = 0;
        }

        $this->assign('loadMoreLabel', OW::getLanguage()->text('base', 'comment_load_more_label'));

        static $dataInit = false;

        if ( !$dataInit )
        {
            $staticDataArray = array(
                'respondUrl'      => OW::getRouter()->urlFor('SPODPUBLIC_CTRL_Comments', 'getCommentList'),//when page button is being pressed
                'delUrl'          => OW::getRouter()->urlFor('SPODPUBLIC_CTRL_Comments', 'deleteComment'),
                'addUrl'          => OW::getRouter()->urlFor('SPODPUBLIC_CTRL_Comments', 'addComment'),
                'delAtchUrl'      => OW::getRouter()->urlFor('SPODPUBLIC_CTRL_Comments', 'deleteCommentAtatchment'),
                'delConfirmMsg'   => OW::getLanguage()->text('base', 'comment_delete_confirm_message'),
                'preloaderImgUrl' => OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'ajax_preloader_button.gif'
            );
            OW::getDocument()->addOnloadScript("window.owCommentListCmps.staticData=" . json_encode($staticDataArray) . ";");
            $dataInit = true;
        }

        $jsParams = json_encode(
            array(
                'totalCount'         => $this->commentCount,
                'contextId'          => $this->cmpContextId,
                'displayType'        => $this->params->getDisplayType(),
                'entityType'         => $this->params->getEntityType(),
                'entityId'           => $this->params->getEntityId(),
                'pagesCount'         => $pagesCount,
                'initialCount'       => $this->params->getInitialCommentsCount(),
                'loadMoreCount'      => $this->params->getLoadMoreCount(),
                'commentIds'         => $this->commentIdList,
                'pages'              => $pages,
                'pluginKey'          => $this->params->getPluginKey(),
                'ownerId'            => $this->params->getOwnerId(),
                'commentCountOnPage' => $this->params->getCommentCountOnPage(),
                'cid'                => $this->id,
                'actionArray'        => $this->actionArr,
                'countToLoad'        => $countToLoad
            )
        );

        OW::getDocument()->addOnloadScript(
            "window.owCommentListCmps.items['$this->id'] = new SpodpublicCommentsList($jsParams);
             window.owCommentListCmps.items['$this->id'].init();"
        );
    }
	
	
	public function itemHandler( BASE_CLASS_EventProcessCommentItem $e )
    {
        $language = OW::getLanguage();

        $deleteButton = false;
        $cAction = null;
        $value = $e->getItem();

        if ( /*$this->isOwnerAuthorized ||*/ $this->isModerator || (int) OW::getUser()->getId() === (int) $value->getUserId() )
        {
            $deleteButton = true;
        }

        if ( $this->isBaseModerator || $deleteButton ) {
            $cAction = new BASE_CMP_ContextAction();
            $parentAction = new BASE_ContextAction();
            $parentAction->setKey('parent');
            $parentAction->setClass('ow_comments_context');
            $cAction->addAction($parentAction);

            if ($deleteButton) {
                $delAction = new BASE_ContextAction();
                $delAction->setLabel($language->text('base', 'contex_action_comment_delete_label'));
                $delAction->setKey('udel');
                $delAction->setParentKey($parentAction->getKey());
                $delId = 'del-' . $value->getId();
                $delAction->setId($delId);
                $this->actionArr['comments'][$delId] = $value->getId();
                $cAction->addAction($delAction);
            }

            if ($this->isBaseModerator && $value->getUserId() != OW::getUser()->getId()) {
                $modAction = new BASE_ContextAction();
                $modAction->setLabel($language->text('base', 'contex_action_user_delete_label'));
                $modAction->setKey('cdel');
                $modAction->setParentKey($parentAction->getKey());
                $delId = 'udel-' . $value->getId();
                $modAction->setId($delId);
                $this->actionArr['users'][$delId] = $value->getUserId();
                $cAction->addAction($modAction);
            }
        }

        if ( $this->params->getCommentPreviewMaxCharCount() > 0 && mb_strlen($value->getMessage()) > $this->params->getCommentPreviewMaxCharCount() )
        {
            $e->setDataProp('previewMaxChar', $this->params->getCommentPreviewMaxCharCount());
        }

        $e->setDataProp('cnxAction', empty($cAction) ? '' : $cAction->render());
    }

    protected function getEntityLevel($id){
        /*$comment = BOL_CommentService::getInstance()->findComment($id);
        $level = 1;
        while($comment = BOL_CommentService::getInstance()->findComment($comment->getCommentEntityId())) $level++;
        return $level;*/

        $comment = BOL_CommentService::getInstance()->findComment($id);
        $level = 0;
        while($comment)
        {
            $entity = BOL_CommentEntityDao::getInstance()->findById($comment->getCommentEntityId());
            $comment = BOL_CommentService::getInstance()->findComment($entity->entityId);
            $level++;
        }
        return $level - 1;

    }

    protected function processList( $commentList )
    {
        /* @var $value BOL_Comment */
        foreach ( $commentList as $value )
        {
            $this->userIdList[] = $value->getUserId();
            $this->commentIdList[] = $value->getId();
        }

        $userAvatarArrayList = empty($this->staticData['avatars']) ? $this->avatarService->getDataForUserAvatars($this->userIdList) : $this->staticData['avatars'];


        foreach ( $commentList as $value )
        {
            /*Add nasted level*/
            if(!isset($this->params->level)) $this->params->level = $this->getEntityLevel($value->getId());

            if($this->params->level <= 2) {
                //nasted comment
                $commentsParams = new BASE_CommentsParams('spodpublic', SPODPUBLIC_BOL_Service::ENTITY_TYPE_COMMENT);
                $commentsParams->setEntityId($value->getId());
                $commentsParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_WITH_LOAD_LIST_MINI);
                $commentsParams->setCommentCountOnPage(5);
                $commentsParams->setOwnerId((OW::getUser()->getId()));
                $commentsParams->setAddComment(TRUE);
                $commentsParams->setWrapInBox(false);
                $commentsParams->setShowEmptyList(false);
                $commentsParams->level = $this->params->level + 1;
                $commentsParams->setCommentPreviewMaxCharCount($this->params->getCommentPreviewMaxCharCount());
                $commentsParams->setInitialCommentsCount($this->params->getInitialCommentsCount());

                $datalet = ODE_BOL_Service::getInstance()->getDataletByPostIdWhereArray($value->getId(), array("comment", "public-room"));

                if(!empty($datalet)) {

                    //OW::getDocument()->addOnloadScript('$("#datalet_placeholder_' . $value->getId() . '_comment").css("display", "none");');

                    /*OW::getDocument()->addOnloadScript('
                               $("#comment_bar_' . $value->getId() . '").append("<paper-fab mini class=\'show_datalet\' icon=\'assessment\' style=\'float:left;\' id=\'show_datalet_comment_' . $value->getId() .'\'></paper-fab>");
                               $("#show_datalet_comment_' . $value->getId() .'").click(function(){
                                     $("#datalet_placeholder_' . $value->getId() . '_comment").toggle(\'fade\',
                                                                                          {direction: \'top\'},
                                                                                          function(){
                                                                                             if($("#datalet_placeholder_' . $value->getId() . '_comment").css(\'display\') == \'none\'){
                                                                                                $("#show_datalet_comment_' . $value->getId() . '").css(\'background\', \'#2196F3\');
                                                                                             }
                                                                                             else
                                                                                                $("#show_datalet_comment_' . $value->getId() . '").css(\'background\', \'#5B646A\');

                                                                                                //resize the datalet when is opened
                                                                                                var datalet = $($("#datalet_placeholder_' . $value->getId() . '_comment").children()[1])[0];
                                                                                                if(datalet.refresh != undefined)
                                                                                                    datalet.refresh();
                                                                                                else
                                                                                                    datalet.behavior.presentData();
                                                                                          },
                                                                                          500);
                                     $("#topic_container").scrollTop($(\'#datalet_placeholder_' . $value->getId() . '_comment\').offset().top - 50);
                               });
                    ');*/

                    OW::getDocument()->addOnloadScript('$("#comment_' . $value->getId() . '").append("<paper-fab mini class=\'show_datalet\' icon=\'assessment\' style=\'float:left; margin-top: 5px;\' id=\'show_datalet_comment_' . $value->getId() .'\'></paper-fab>");');
                    
                }

                $this->addComponent('nestedComments' . $value->getId(), new SPODPUBLIC_CMP_Comments($commentsParams));

/*                OW::getDocument()->addOnloadScript(
                    "$(document).ready(function(){
                        $('#spod_public_room_nested_comment_show_" . $value->getId() . "').click(function(){
                              $('#nc_" . $value->getId() . "').toggle('fade', {direction: 'top'}, 500);
                              var d = $('#nc_" . $value->getId() . "').css('display');
                              if($('#spod_public_room_nested_comment_show_" . $value->getId() . "').css('background-position') == '-38px -38px'){
                                 $('#spod_public_room_nested_comment_show_" . $value->getId() . "').css('background-position', '-38px 0px');
                              }else{
                                 $('#spod_public_room_nested_comment_show_" . $value->getId() . "').css('background-position', '-38px -38px');
                              }
                           });
                    });"
                );
*/

                @$this->assign('commentSentiment' . $value->getId(), SPODPUBLIC_BOL_Service::getInstance()->getCommentSentiment($value->getId())->sentiment);
                $this->assign('commentsCount' . $value->getId(), BOL_CommentService::getInstance()->findCommentCount(SPODPUBLIC_BOL_Service::ENTITY_TYPE_COMMENT, $value->getId()));
                $this->assign('commentsLevel' . $value->getId(), $this->params->level);
            }

            /*End adding nasted level*/

            $cmItemArray = array(
                'displayName' => $userAvatarArrayList[$value->getUserId()]['title'],
                'avatarUrl'   => $userAvatarArrayList[$value->getUserId()]['src'],
                'profileUrl'  => $userAvatarArrayList[$value->getUserId()]['url'], 
                'content'     => $value->getMessage(),
                'date'        => UTIL_DateTime::formatDate($value->getCreateStamp()),
                'userId'      => $value->getUserId(),
                'commentId'   => $value->getId(),
                'avatar'      => $userAvatarArrayList[$value->getUserId()]
            );

            $contentAdd = '';

            if ( $value->getAttachment() !== null )
            {
                //$tempCmp = new BASE_CMP_OembedAttachment((array) json_decode($value->getAttachment()), $this->isOwnerAuthorized);
                $tempCmp = new SPODTCHAT_CMP_TchatOembedAttachment((array) json_decode($value->getAttachment()), $this->isOwnerAuthorized);
                $contentAdd .= '<div class="ow_attachment ow_small" id="att' . $value->getId() . '">' . $tempCmp->render() . '</div>';
            }

            $cmItemArray['content_add'] = $contentAdd;

            $event = new BASE_CLASS_EventProcessCommentItem('base.comment_item_process', $value, $cmItemArray);
            OW::getEventManager()->trigger($event);
            $arrayToAssign[] = $event->getDataArr();

        }

        return (isset($arrayToAssign)) ? $arrayToAssign : array();
    }
}
