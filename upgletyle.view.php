<?php

    /**
     * @class  upgletyleView
     * @author UPGLE (admin@upgle.com)
     * @brief  upgletyle module View class
     **/

    class upgletyleView extends upgletyle {

        /**
         * @brief Initialization
         **/
        function init() {

			$act = Context::get('act');

            $oUpgletyleModel = &getModel('upgletyle');
            if(preg_match("/UpgletyleTool/",$this->act) || $oUpgletyleModel->isAttachedMenu($this->act) ) {
				if(__DEBUG__)
				{
					Context::loadFile(array('./modules/admin/tpl/js/jquery.tmpl.js', '', '', 1), true);
					Context::loadFile(array('./modules/admin/tpl/js/jquery.jstree.js', '', '', 1), true);
				} 
				else
				{
					Context::loadFile(array('./modules/admin/tpl/js/jquery.tmpl.js', '', '', 1), true);
					Context::loadFile(array('./modules/admin/tpl/js/jquery.jstree.js', '', '', 1), true);
				}
                $this->initTool($this);

            } else {
                $this->initService($this);
            }
        }

        /**
         * @brief Upgletyle common init
         **/
        function initCommon($is_other_module = false){

            if(!$this->checkXECoreVersion('1.4.3')) return $this->stop(sprintf(Context::getLang('msg_requried_version'),'1.4.3'));

			$oUpgletyleModel = &getModel('upgletyle');
			$oUpgletyleController = &getController('upgletyle');
            $oModuleModel = &getModel('module');

            $site_module_info = Context::get('site_module_info');
            if(!$this->module_srl) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = $site_module_info->site_srl;
                if($site_srl) {
                    $this->module_srl = $site_module_info->index_module_srl;
                    $this->module_info = $oModuleModel->getModuleInfoByModuleSrl($this->module_srl);
                    if (!$is_other_module){
                        Context::set('module_info',$this->module_info);
                        Context::set('mid',$this->module_info->mid);
                        Context::set('current_module_info',$this->module_info);
                    }
                }
            }

            if(!$this->module_info->skin) $this->module_info->skin = $this->skin;

            $preview_skin = Context::get('preview_skin');
            if($oModuleModel->isSiteAdmin(Context::get('logged_info'))&&$preview_skin) {
                if(is_dir($this->module_path.'skins/'.$preview_skin)) {
                    $upgletyle_config->skin = $this->module_info->skin = $preview_skin;
                }
            }

            if (!$is_other_module){
                Context::set('module_info',$this->module_info);
                Context::set('current_module_info', $this->module_info);
            }

            $this->upgletyle = $oUpgletyleModel->getUpgletyle($this->module_info->module_srl);
            $this->site_srl = $this->upgletyle->site_srl;
            Context::set('upgletyle',$this->upgletyle);
            Context::set('textyle',$this->upgletyle);

            if($this->upgletyle->timezone) $GLOBALS['_time_zone'] = $this->upgletyle->timezone;

            Context::addHtmlHeader('<link rel="shortcut icon" href="'.$this->upgletyle->getFaviconSrc().'" />');

            // publish subscription
            if($this->upgletyle->getSubscriptionDate() <= date('YmdHis')){
                $output = $oUpgletyleController->publishSubscriptedPost($this->module_info->module_srl);
            }
        }

        /**
         * @brief Upgletyle init tool
         **/
        function initTool(&$oModule, $is_other_module = false){
            if (!$oModule) $oModule = $this;

            $this->initCommon($is_other_module);

            $oUpgletyleModel = &getModel('upgletyle');

            $site_module_info = Context::get('site_module_info');
            $upgletyle = $oUpgletyleModel->getUpgletyle($site_module_info->index_module_srl);
            $custom_menu = $oUpgletyleModel->getUpgletyleCustomMenu();

            $info = Context::getDBInfo();
            if($info->use_mobile_vie=='Y'){
                $custom_menu->hidden_menu[] = strtolower('dispUpgletyleToolLayoutConfigMobileSkin');
            }

            Context::set('custom_menu', $custom_menu);


            if($oUpgletyleModel->ishiddenMenu($oModule->act) || ($oModule->act == 'dispUpgletyleToolDashboard' && $oUpgletyleModel->isHiddenMenu(0)) ) {
                if($oUpgletyleModel->isHiddenMenu(0)) Context::set('act', $oModule->act = 'dispUpgletyleToolPostManageList', true);
                else Context::set('act', $oModule->act= 'dispUpgletyleToolDashboard', true);
            }

            if ($is_other_module){
                $oModule->setLayoutPath($this->module_path.'tpl');
                $oModule->setLayoutFile('_tool_layout');
            }else{
                $template_path = sprintf("%stpl",$this->module_path);
                $this->setTemplatePath($template_path);
                $this->setTemplateFile(str_replace('dispUpgletyleTool','',$this->act));
            }

            if($_COOKIE['tclnb']) Context::addBodyClass('lnbClose');
            else Context::addBodyClass('lnbToggleOpen');

			//회원이 가지고 있는 블로그리스트를 구함
            $args->list_count = 20;
            $args->list_order = 'regdate';
			$args->s_member_srl = 4;
            $output = $oUpgletyleModel->getUpgletyleList($args);
            if(!$output->toBool()) return $output;
			Context::set('bloglist',$output->data);

            // set browser title 
            Context::setBrowserTitle($upgletyle->get('browser_title') . ' - admin');
        }

        /**
         * @brief upgletyle init service
         **/
        function initService(&$oModule, $is_other_module = false, $isMobile = false){
            if (!$oModule) $oModule = $this;

            $oUpgletyleModel = &getModel('upgletyle');

            $this->initCommon($is_other_module);

            Context::addJsFile($this->module_path.'tpl/js/upgletyle_service.js');

            $preview_skin = Context::get('preview_skin');
			if(!$isMobile)
			{
				if($is_other_module){
					$path_method = 'setLayoutPath';
					$file_method = 'setLayoutFile';
					$css_path_method = 'getLayoutPath';
					Context::set('upgletyle_mode', 'module');
					Context::set('textyle_mode', 'module'); //for textyle skins

				}else{
					$path_method = 'setTemplatePath';
					$file_method = 'setTemplateFile';
					$css_path_method = 'getTemplatePath';
				}

				if(!$preview_skin){
					$oUpgletyleModel->checkUpgletylePath($this->module_srl, $this->module_info->skin);
					$oModule->{$path_method}($oUpgletyleModel->getUpgletylePath($this->module_srl));
				}else{
					$oModule->{$path_method}($this->module_path.'skins/'.$preview_skin);
				}

				$oModule->{$file_method}('upgletyle');
				Context::addCssFile($oModule->{$css_path_method}().'upgletyle.css',true,'all','',100);
			}

            Context::set('root_url', Context::getRequestUri());
            Context::set('home_url', getFullSiteUrl($this->upgletyle->domain));
            Context::set('profile_url', getSiteUrl($this->upgletyle->domain,'','mid',$this->module_info->mid,'act','dispUpgletyleProfile'));
            Context::set('guestbook_url', getSiteUrl($this->upgletyle->domain,'','mid',$this->module_info->mid,'act','dispUpgletyleGuestbook'));
            Context::set('tag_url', getSiteUrl($this->upgletyle->domain,'','mid',$this->module_info->mid,'act','dispUpgletyleTag'));
            if(Context::get('is_logged')) Context::set('admin_url', getSiteUrl($this->upgletyle->domain,'','mid',$this->module_info->mid,'act','dispUpgletyleToolDashboard'));
            else Context::set('admin_url', getSiteUrl($upgletyle->domain,'','mid','upgletyle','act','dispUpgletyleToolLogin'));
            Context::set('upgletyle_title', $this->upgletyle->get('upgletyle_title'));
            Context::set('textyle_title', $this->upgletyle->get('upgletyle_title')); //for textyle skins
			if($this->upgletyle->get('post_use_prefix')=='Y' && $this->upgletyle->get('post_prefix')) Context::set('post_prefix', $this->upgletyle->get('post_prefix'));
            if($this->upgletyle->get('post_use_suffix')=='Y' && $this->upgletyle->get('post_suffix')) Context::set('post_suffix', $this->upgletyle->get('post_suffix'));

            $extra_menus = array();
            $args->site_srl = $this->site_srl;
            $output = executeQueryArray('upgletyle.getExtraMenus',$args);
            if($output->toBool() && $output->data){
                foreach($output->data as $i => $menu){
                    $extra_menus[$menu->name] = getUrl('','mid',$menu->mid);
                }
            }

            Context::set('extra_menus', $extra_menus);

            // set browser title 
            Context::setBrowserTitle($this->upgletyle->get('browser_title'));
        }

        /**
         * @brief rss for publish subscription
         **/
        function rss(){
            $oRss = &getView('rss');
            $oRss->module_info = $this->module_info;
            $oRss->rss();
            $this->setTemplatePath($oRss->getTemplatePath());
            $this->setTemplateFile($oRss->getTemplateFile());
        }

        /**
         * @brief Tool dashboard
         **/
        function dispUpgletyleToolDashboard(){

			global $lang;

            $oUpgletyleModel = &getModel('upgletyle');

            $oCounterModel = &getModel('counter');
            $counter = $oCounterModel->getStatus(array(0,date("Ymd")),$this->site_srl);
            $status->total_visitor = $counter[0]->unique_visitor;
            $status->visitor = $counter[date("Ymd")]->unique_visitor;
            Context::set('status', $status);

			//차트 출력 (이번주 / 저번주)
			$detail_status = $oCounterModel->getHourlyStatus('week', date("Ymd",time()), $this->site_srl);
			$i=0;
			foreach($detail_status->list as $key => $val) {
				$_k = $lang->unit_week[date('l',strtotime($key))];
				$chart_ticks[] = sprintf("[%d, \"%s\"]", $i, $_k);
				$chart_value_this[] = sprintf("[%d, %d]", $i, $val);
				$i++;
			}
			$last_date = date("Ymd",strtotime(date("Ymd",time()))-60*60*24*7);
			$last_detail_status = $oCounterModel->getHourlyStatus('week', $last_date, $this->site_srl);

			$i=0;
			foreach($last_detail_status->list as $key => $val) {
				$chart_value_last[] = sprintf("[%d, %02d]", $i++, $val);
			}
			Context::set('chart_ticks',implode(",",$chart_ticks));
			Context::set('chart_value_this',implode(",",$chart_value_this));
			Context::set('chart_value_last',implode(",",$chart_value_last));


            $doc_args->module_srl = array($this->upgletyle->get('member_srl'), $this->module_srl);
            $doc_args->sort_index = 'list_order';
            $doc_args->order_type = 'asc';
            $doc_args->list_count = 4;
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($doc_args, false, false);
            Context::set('newest_documents', $output->data);

            $com_args->module_srl = $this->upgletyle->get('module_srl');
            $com_args->sort_index = 'list_order';
            $com_args->order_type = 'asc';
            $com_args->list_count = 5;
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($com_args);
            Context::set('newest_comments', $output->data);


			$total_published_post = $oDocumentModel->getDocumentCount($this->module_srl);
            Context::set('total_published_post', $total_published_post);

			$total_comment = $oCommentModel->getCommentAllCount($this->module_srl);
            Context::set('total_comment', $total_comment);

			$oModule = getModule('trackback');
			$total_trackback = '-';
			if($oModule)
			{
		        $oTrackbackModel = &getModel('trackback');
				$total_trackback = $oTrackbackModel->getTrackbackAllCount($this->module_srl);
			}
            Context::set('total_trackback', $total_trackback);

			$total_guestbook = $oUpgletyleModel->getUpgletyleGuestbookAllCount($this->module_srl);
            Context::set('total_guestbook', $total_guestbook);


            unset($args);
            $args->module_srl = $this->module_srl;
            $args->page = 1;
            $args->list_count = 5;

            $output = $oUpgletyleModel->getUpgletyleGuestbookList($args);
            Context::set('guestbook_list',$output->data);
        }

        /**
         * @brief Login
         **/
        function dispUpgletyleToolLogin() {
            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');
            Context::set('enable_openid', $member_config->enable_openid);

            Context::addBodyClass('logOn');
        }

        /**
         * @brie display textule tool post manage write
         **/
        function dispUpgletyleToolPostManageWrite(){
            // set filter
            Context::addJsFilter($this->module_path.'tpl/filter', 'save_post.xml');

            $oDocumentModel = &getModel('document');
            $document_srl = Context::get('document_srl');
            $material_srl = Context::get('material_srl');

            if($document_srl){
                $oDocument = $oDocumentModel->getDocument($document_srl,false,false);
            }else{
                $document_srl=0;
                $oDocument = $oDocumentModel->getDocument(0);
                if($material_srl){
                    $oMaterialModel = &getModel('material');
                    $output = $oMaterialModel->getMaterial($material_srl);
                    if($output->data){
                        $material_content = $output->data[0]->content;
                        Context::set('material_content',$material_content);
                    }
                }
            }
            $category_list = $oDocumentModel->getCategoryList($this->module_srl);
            Context::set('category_list',$category_list);

            $oTagModel = &getModel('tag');
            $args->module_srl = $this->module_srl;
            $args->list_count = 20;
            $output = $oTagModel->getTagList($args);
            Context::set('tag_list',$output->data);

            $oEditorModel = &getModel('editor');
            $option->skin = $this->upgletyle->getPostEditorSkin();
            $option->primary_key_name = 'document_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = true;
            $option->enable_autosave = true;
            $option->enable_default_component = true;
            $option->enable_component = $option->skin =='dreditor' ? false : true;
            $option->resizable = true;
            $option->height = 500;
            $option->content_font = $this->upgletyle->getFontFamily();
            $option->content_font_size = $this->upgletyle->getFontSize();
            $editor = $oEditorModel->getEditor($document_srl, $option);
            Context::set('editor', $editor);
            Context::set('editor_skin', $option->skin);

            // permalink
            $permalink = '';
            if(isSiteID($this->upgletyle->domain)){
                if(Context::isAllowRewrite()){
                    $permalink = getFullSiteUrl($this->upgletyle->domain,'') . '/entry/';
                }else{
                    $permalink = getFullSiteUrl($this->upgletyle->domain).'?vid='.$this->upgletyle->domain . '&mid='.Context::get('mid').'&entry=';
                }
            }else{
                if(Context::isAllowRewrite()){
                    $permalink = getFullSiteUrl($this->upgletyle->domain,'').'entry/';
                }else{
                    $premalink = getFullSiteUrl($this->upgletyle->domain,'','mid',Context::get('mid')).'&entry=';
                }
            }
            Context::set('permalink',$permalink);
            $oUpgletyleModel = &getModel('upgletyle');

            $alias = $oDocumentModel->getAlias($document_srl);
            Context::set('alias',$alias);

            $output = $oUpgletyleModel->getSubscriptionByDocumentSrl($document_srl);
            if($output->data){
                $publish_date = $output->data[0]->publish_date;
                $publish_date = sscanf($publish_date,'%04d%02d%02d%02d%02d');
                Context::set('publish_date_yyyymmdd',sprintf("%s-%02d-%02d",$publish_date[0],$publish_date[1],$publish_date[2]));
                Context::set('publish_date_hh',sprintf("%02d",$publish_date[3]));
                Context::set('publish_date_ii',sprintf("%02d",$publish_date[4]));
                Context::set('subscription','Y');
            }

            if($oDocument->get('module_srl') != $this->module_srl && !$document_srl){
                Context::set('from_saved',true);
            }
            $oPublish = $oUpgletyleModel->getPublishObject($this->module_srl, $oDocument->document_srl);
            if(count($oPublish->trackbacks)) $trackbacks = $oPublish->getTrackbacks();
            if(count($oPublish->blogapis)) $_apis = $oPublish->getApis();
			

            Context::set('oDocument', $oDocument);
            Context::set('oUpgletyle', $oUpgletyleModel->getUpgletyle($this->module_srl));
            Context::set('oPublish', $oPublish);
            Context::set('category_list', $oDocumentModel->getCategoryList($this->module_srl));
            Context::set('trackbacks', $trackbacks);
            Context::set('_apis', $_apis);

			//Set a Meta box 
			$_metabox = new stdClass();
			$triggerOutput = ModuleHandler::triggerCall('upgletyle.ToolPostManageWrite', 'metabox', $_metabox);
			if(!$triggerOutput->toBool())
			{
				return $triggerOutput;
			}
			foreach($_metabox as $val=>$key) $metabox .= $key;
			Context::set('metabox', $metabox);
        }

        /**
         * @brief display upgletyle tool post manage publish
         **/
        function dispUpgletyleToolPostManagePublish() {
            $oDocumentModel = &getModel('document');
            $oUpgletyleModel = &getModel('upgletyle');

            $document_srl = Context::get('document_srl');
            if(!$document_srl) return new Object(-1,'msg_invalid_request');

            $oDocument = $oDocumentModel->getDocument($document_srl,false,false);
            if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');

            $alias = $oDocumentModel->getAlias($document_srl);
            Context::set('alias',$alias);

            $output = $oUpgletyleModel->getSubscriptionByDocumentSrl($document_srl);
            if($output->data){
                $publish_date = $output->data[0]->publish_date;
                $publish_date = sscanf($publish_date,'%04d%02d%02d%02d%02d');
                Context::set('publish_date_yyyymmdd',sprintf("%s-%02d-%02d",$publish_date[0],$publish_date[1],$publish_date[2]));
                Context::set('publish_date_hh',sprintf("%02d",$publish_date[3]));
                Context::set('publish_date_ii',sprintf("%02d",$publish_date[4]));
                Context::set('subscription','Y');
            }

            if($oDocument->get('module_srl') != $this->module_srl){
                Context::set('from_saved',true);
            }

            Context::set('oDocument', $oDocument);
            Context::set('oUpgletyle', $oUpgletyleModel->getUpgletyle($this->module_srl));
            Context::set('oPublish', $oUpgletyleModel->getPublishObject($this->module_srl, $oDocument->document_srl));
            Context::set('category_list', $oDocumentModel->getCategoryList($this->module_srl));

            Context::addJsFilter($this->module_path.'tpl/filter', 'publish_post.xml');
        }

        /**
         * @brief Document Alias check (API)
         **/
        function dispUpgletylePostCheckAlias(){
            $mid = Context::get('mid');
            $alias = Context::get('alias');
            $oDocumentModel = &getModel('document');
            $document_srl = $oDocumentModel->getDocumentSrlByAlias($mid,$alias);
            Context::set('document_srl',$document_srl);
        }

        /**
         * @brief display upgletyle tool post manage list
         **/
        function dispUpgletyleToolPostManageList(){

            $args->page = Context::get('page');
            if(!$args->page) $args->page = 1;
            Context::set('page',$args->page);

            $args->search_target = Context::get('search_target');
            $args->search_keyword = Context::get('search_keyword');
            $args->category_srl = Context::get('search_category_srl');
            $args->sort_index = Context::get('sort_index');
            //$args->order_type = Context::get('order_type');
			$args->list_count = 18;

            $published = Context::get('published');
            $logged_info = Context::get('logged_info');

            if(!$published){
                $args->module_srl = array($this->module_srl,$this->module_srl * -1,$logged_info->member_srl);
            }else if($published > 0){
                $args->module_srl = $this->module_srl;
            }else{
                $args->module_srl = array($logged_info->member_srl,$this->module_srl * -1);
            }

            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args, false, false);
			$post_list = $output->data;

			//Type 설정
			$oUpgletyleModel = &getModel('upgletyle');
			foreach($post_list as $key => $val) {
				$output2 = $oUpgletyleModel->getSubscriptionByDocumentSrl($val->document_srl);
				if($output2->data && $val->get('module_srl')<=0) $val->type = 'reserve';
				elseif(!$output2->data && $val->get('module_srl')<=0) $val->type = 'save';
				elseif($val->get('module_srl') == $logged_info->member_srl) $val->type = 'temp';
				else $val->type = 'publish';				
			}

			$onoff = array('' => 'on', 0 => 'on', 1 => 'off');
			Context::set('onoff',$onoff);

			$offon = array('' => 'off', 0 => 'off', 1 => 'on');
			Context::set('offon',$offon);

            Context::set('post_list',$output->data);
            Context::set('page_navigation', $output->page_navigation);

            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($this->module_srl);
            Context::set('category_list', $category_list);

            foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);
            Context::set('search_option', $search_option);

            Context::addJsFilter($this->module_path.'tpl/filter', 'update_allow.xml');
        }

        /**
         * @brief display upgletyle tool post manage deposit
         **/
        function dispUpgletyleToolPostManageDeposit(){
            $oMaterialModel = &getModel('material');

            $page = Context::get('page');
            $logged_info = Context::get('logged_info');
            $args->page = $page;
            $args->member_srl = $logged_info->member_srl;

            if($oMaterialModel) {
                $output = $oMaterialModel->getMaterialList($args);
                $bookmark_url = $oMaterialModel->getBookmarkUrl($logged_info->member_srl);

                Context::set('page',$output->page_navigation->cur_page);
                Context::set('bookmark_url',$bookmark_url);
                Context::set('material_list',$output->data);
                Context::set('page_navigation',$output->page_navigation);
            } else {
                Context::set('bookmark_url','#');
            }

            Context::set('containerClassName','ece');
        }

        /**
         * @brief display upgletyle tool post manage category
         **/
        function dispUpgletyleToolPostManageCategory(){
            $oUpgletyleModel = &getModel('upgletyle');
            $catgegory_content = $oUpgletyleModel->getCategoryHTML($this->module_srl);

            Context::set('module_srl',$this->module_srl);
            Context::set('category_content', $catgegory_content);
            Context::set('module_info', $this->module_info);
        }

        /**
         * @brief display upgletyle tool post manage tag
         **/
        function dispUpgletyleToolPostManageTag(){
            $args->module_srl = $this->module_srl;
            $args->list_count = 100000;
            $args->sort_index = Context::get('sort_index');

            $oTagModel = &getModel('tag');
            $output = $oTagModel->getTagList($args);
            Context::set('tag_list',$output->data);
            Context::set('tag_list_count',count($output->data));

            $args->list_count = 10;
            $args->sort_index = 'regdate';
            $output = $oTagModel->getTagList($args);
            Context::set('tag_recent_list',$output->data);

            unset($args);
            $args->tag = Context::get('selected_tag');
            if($args->tag){
                $args->module_srl = $this->module_srl;
                $output = $oTagModel->getTagWithUsedList($args);
                Context::set('with_used_tag_list',$output->data);
            }
        }

        /**
         * @brief display upgletyle tool communication comment
         **/
        function dispUpgletyleToolCommunicationComment(){
            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_denylist.xml');

            $args->page = Context::get('page'); 
            $args->search_keyword = Context::get('search_keyword');
            $args->search_target = Context::get('search_target');

            $args->list_count = 30; 
            $args->page_count = 10; 

            $args->sort_index = 'list_order';

            $args->module_srl = $this->upgletyle->module_srl;

            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($args);
            Context::set('comment_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('page', $output->page);
        }

        /**
         * @brief display upgletyle tool communication comment reply
         **/
        function dispUpgletyleToolCommunicationCommentReply(){
            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

            $parent_srl = Context::get('comment_srl');
            $document_srl = Context::get('document_srl');

            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            $oCommentModel = &getModel('comment');
            $oSourceComment = $oCommentModel->getComment($parent_srl);

            if(!$oSourceComment->isExists()) return $this->dispUpgletyleMessage('msg_invalid_request');

            if($document_srl && $oSourceComment->get('document_srl') != $document_srl) return $this->dispUpgletyleMessage('msg_invalid_request');

            $oComment = $oCommentModel->getComment(0);
            $oComment->add('parent_srl', $parent_srl);
            $oComment->add('document_srl', $oSourceComment->get('document_srl'));

            Context::set('oSourceComment',$oSourceComment);
            Context::set('oComment',$oComment);
            Context::set('module_srl',$this->upgletyle->module_srl);
            Context::set('upgletyle_mode','comment_form');
            Context::set('textyle_mode','comment_form'); //for textyle skins

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');
        }

        /**
         * @brief display upgletyle tool communication guestbook
         **/
        function dispUpgletyleToolCommunicationGuestbook(){
            $page = Context::get('page');
            if(!$page) $page = 1;
            Context::set('page',$page);

            $args->search_keyword = Context::get('search_keyword');
            $args->module_srl = $this->module_srl;
            $args->page = $page;

            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getUpgletyleGuestbookList($args);
            Context::set('guestbook_list',$output->data);
            Context::set('page_navigation',$output->page_navigation);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_denylist.xml');
        }

        /**
         * @brief tool Guestbook Reply
         **/
        function dispUpgletyleToolCommunicationGuestbookReply(){
            $upgletyle_guestbook_srl = Context::get('upgletyle_guestbook_srl');
            $page = Context::get('page');
            if(!$page) $page = 1;
            Context::set('page',$page);

            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getUpgletyleGuestbook($upgletyle_guestbook_srl);
            Context::set('guestbook_list',$output->data);

            $oEditorModel = &getModel('editor');
            $option->skin = $this->upgletyle->get('guestbook_editor_skin');
            $option->colorset = $this->upgletyle->get('guestbook_editor_colorset');
            $option->primary_key_name = 'parent_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = false;
            $option->enable_component = false;
            $option->resizable = false;
            $option->disable_html = true;
            $option->height = 200;
            $editor = $oEditorModel->getEditor(0, $option);
            Context::set('editor', $editor);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_guestbook_reply.xml');
        }

        /**
         * @brief display upgletyle tool communication trackback
         **/
        function dispUpgletyleToolCommunicationTrackback(){
            $args->module_srl = $this->module_srl;
            $args->search_target = Context::get('search_target');
            $args->search_keyword = Context::get('search_keyword');

            $oTrackbackAdminModel = &getAdminModel('trackback');
            $output = $oTrackbackAdminModel->getTotalTrackbackList($args);

            $document_srl = array();
            if(count($output->data)>0){
                foreach($output->data as $k => $v) $document_srl[] = $v->document_srl;

                $oDocumentModel = &getModel('document');
                $document_items = $oDocumentModel->getDocuments($document_srl,false,false);
            }

            Context::set('trackback_list',$output->data);
            Context::set('document_items',$document_items);
            Context::set('page_navigation',$output->page_navigation);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_denylist.xml');
        }

        /**
         * @brief display upgletyle tool communication spam
         **/
        function dispUpgletyleToolCommunicationSpam(){
            $oUpgletyleModel = &getModel('upgletyle');
            $deny_list = $oUpgletyleModel->getUpgletyleDenyList($this->module_srl);
            Context::set('deny_list',$deny_list);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_deny.xml');
        }

        /**
         * @brief display upgletyle tool statistics visitor
         **/
        function dispUpgletyleToolStatisticsVisitor() {
            global $lang;

            $selected_date = Context::get('selected_date');
            if(!$selected_date) $selected_date = date("Ymd");
            Context::set('selected_date', $selected_date);

            $oCounterModel = &getModel('counter');

            $type = Context::get('type');
            if(!$type) {
                $type = 'day';
                Context::set('type',$type);
            }

            $site_module_info = Context::get('site_module_info');

			$chart_ticks = array();
			$chart_value_this = array();
			$chart_value_last = array();

            $xml->item = array();
            $xml->value = array(array(),array());
            $selected_count = 0;

            // total & today
            $counter = $oCounterModel->getStatus(array(0,date("Ymd")),$site_module_info->site_srl);
            $total->total = $counter[0]->unique_visitor;
            $total->today = $counter[date("Ymd")]->unique_visitor;

            switch($type) {
                case 'month' :
                        $xml->selected_title = Context::getLang('this_month');
                        $xml->last_title = Context::getLang('before_month');

                        $disp_selected_date = date("Y", strtotime($selected_date));
                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24*365));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24*365));
                        $detail_status = $oCounterModel->getHourlyStatus('month', $selected_date, $site_module_info->site_srl);

						$i=0;
                        foreach($detail_status->list as $key => $val) {
                            $_k = substr($selected_date,0,4).'.'.sprintf('%02d',$key);


							$chart_ticks[] = sprintf("[%d, \"%s\"]", $i, $_k);
							$chart_value_this[] = sprintf("[%d, %d]", $i, $val);


                            $output->list[$_k]->val = $val;
                            if($selected_date == date("Ymd")&&$key == date("m")){
                                $selected_count = $val;
                                $output->list[$_k]->selected = true;
                            }else{
                                $output->list[$_k]->selected = false;
                            }
                            $output->list[$_k]->val = $val;
                            $xml->item[] = sprintf('<item id="%d" name="%s" />',$i++,$_k);
                            $xml->value[0][] = $val;
                        }


                        $last_date = date("Ymd",strtotime($selected_date)-60*60*24*365);
                        $last_detail_status = $oCounterModel->getHourlyStatus('month', $last_date, $site_module_info->site_srl);

						$i=0;
                        foreach($last_detail_status->list as $key => $val) {
							$chart_value_last[] = sprintf("[%d, %d]", $i++, $val);
                        }

                    break;
                case 'week' :
                        $xml->selected_title = Context::getLang('this_week');
                        $xml->last_title = Context::getLang('last_week');

                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24*7));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24*7));
                        $disp_selected_date = date("Y.m.d", strtotime($selected_date));
                        $detail_status = $oCounterModel->getHourlyStatus('week', $selected_date, $site_module_info->site_srl);

						$i=0;
                        foreach($detail_status->list as $key => $val) {
                            $_k = date("Y.m.d", strtotime($key)).' ('.$lang->unit_week[date('l',strtotime($key))].')';
                            $__k = date("m.d", strtotime($key)).' ('.$lang->unit_week[date('l',strtotime($key))].')';

							$chart_ticks[] = sprintf("[%d, \"%s\"]", $i, $__k);
							$chart_value_this[] = sprintf("[%d, %d]", $i, $val);


                            if($selected_date == date("Ymd")&&$key == date("Ymd")){
                                $selected_count = $val;
                                $output->list[$_k]->selected = true;
                            }else{
                                $output->list[$_k]->selected = false;
                            }
                            $output->list[$_k]->val = $val;
							$i++;
                        }

                        $last_date = date("Ymd",strtotime($selected_date)-60*60*24*7);
                        $last_detail_status = $oCounterModel->getHourlyStatus('week', $last_date, $site_module_info->site_srl);
						
						$i=0;
                        foreach($last_detail_status->list as $key => $val) {
							$chart_value_last[] = sprintf("[%d, %02d]", $i, $val);
							$i++;
                        }


                    break;
                case 'day' :
                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24));
                        $disp_selected_date = date("Y.m.d", strtotime($selected_date));

                        $detail_status = $oCounterModel->getHourlyStatus('hour', $selected_date, $site_module_info->site_srl);
						
                        foreach($detail_status->list as $key => $val) {

							$chart_ticks[] = sprintf("[%d, %02d]", $key, $key);
							$chart_value_this[] = sprintf("[%d, %d]", $key, $val);

                            $_k = sprintf('%02d',$key);
                            if($selected_date == date("Ymd")&&$key == date("H")){
                                $selected_count = $val;
                                $output->list[$_k]->selected = true;
                            }else{
                                $output->list[$_k]->selected = false;
                            }
                            $output->list[$_k]->val = $val;
                        }

                        $last_date = date("Ymd",strtotime($selected_date)-60*60*24);
                        $last_detail_status = $oCounterModel->getHourlyStatus('hour', $last_date, $site_module_info->site_srl);

                        foreach($last_detail_status->list as $key => $val) {
							$chart_value_last[] = sprintf("[%d, %02d]", $key, $val);
                        }


                    break;
            }

            //Context::set('xml', urlencode($xml->data));
            Context::set('before_url', $before_url);
            Context::set('after_url', $after_url);
            Context::set('disp_selected_date', $disp_selected_date);

			//flotChart
			Context::set('chart_ticks',implode(",",$chart_ticks));
			Context::set('chart_value_this',implode(",",$chart_value_this));
			Context::set('chart_value_last',implode(",",$chart_value_last));

            $output->sum = $detail_status->sum;
            $output->max = $detail_status->max;
            $output->selected_count = $selected_count;
            $output->total = $total->total;
            $output->today = $total->today;
            Context::set('detail_status', $output);
        }

        /**
         * @brief display upgletyle tool statistics visit route
         **/
        function dispUpgletyleToolStatisticsVisitRoute() {
            global $lang;
            $oDocumentModel = &getModel('document');

            $selected_date = Context::get('selected_date');
            if(!$selected_date) $selected_date = date("Ymd");
            Context::set('selected_date', $selected_date);

            $type = Context::get('type');
            if(!$type) {
                $type = 'day';
                Context::set('type',$type);
            }

            $page = Context::get('page');
            $site_module_info = Context::get('site_module_info');
            $args->module_srl = $this->module_srl;
            $args->page = ($page) ? $page : 1;

            switch($type) {
                case 'month' :
                        $disp_selected_date = date("Y-m", strtotime($selected_date));
                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24*30));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24*30));
                        $args->month = date("Ym",strtotime($selected_date));
                    break;
                case 'week' :
                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24*7));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24*7));

                        $time = strtotime($selected_date);
                        $w = date("D");
                        while(date("D",$time) != "Sun") {
                            $time += 60*60*24;
                        }
                        $time -= 60*60*24;
                        while(date("D",$time)!="Sun") {
                            $thisWeek[] = date("Ymd",$time);
                            $time -= 60*60*24;
                        }
                        $args->start_date = $thisWeek[5];
                        $args->end_date = $thisWeek[0];
                        $disp_selected_date = sprintf("%s-%s", date("Y.m.d",strtotime($args->start_date)),date("Y.m.d",strtotime($args->end_date)));
                    break;
                case 'day' :
                        $disp_selected_date = date("Y.m.d", strtotime($selected_date));
                        $before_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)-60*60*24));
                        $after_url = getUrl('selected_date', date("Ymd",strtotime($selected_date)+60*60*24));
                        $args->day = date("Ymd",strtotime($selected_date));
                    break;
            }


            $host_srl = Context::get('host_srl');
            if($host_srl) {
                $args->textyle_host_srl = $h_args->textyle_host_srl = $host_srl;
                $output = executeQuery('upgletyle.getRefererHost', $h_args);
                Context::set('referer_host', $output->data);
                $output = executeQuery('upgletyle.getRefererMaxVisitor', $args);
                Context::set('max_visitor', $output->data->visitor?$output->data->visitor:1);
                $output = executeQueryArray('upgletyle.getRefererList', $args);
            } else {
                $output = executeQuery('upgletyle.getRefererHostMaxVisitor', $args);
                Context::set('max_visitor', $output->data->visitor?$output->data->visitor:1);
                $output = executeQueryArray('upgletyle.getRefererHostList', $args);
            }
            $document_list = array();
            if($output->data) {
                foreach($output->data as $key => $val) {
                    unset($obj);
                    $obj = new documentItem(0,false);
                    $obj->setAttribute($val, false);
                    $document_list[] = $obj;
                }
            }

            Context::set('before_url', $before_url);
            Context::set('after_url', $after_url);
            Context::set('disp_selected_date', $disp_selected_date);
            Context::set('document_list', $document_list);
            Context::set('page_navigation', $output->page_navigation);
        }

        /**
         * @brief display upgletyle tool statistics supporter
         **/
        function dispUpgletyleToolStatisticsSupporter(){
            $selected_date = Context::get('selected_date');
            if(!$selected_date){
                $selected_date = date('Ymd');
                Context::set('selected_date',$selected_date);
            }

            $sort_index = Context::get('sort_index');
            $sort_index = $sort_index ? $sort_index : 'total_count';

            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getUpgletyleSupporterList($this->module_srl,substr($selected_date,0,6),$sort_index);
            Context::set('supporter_list',$output->data);

            Context::set('disp_selected_date',date("Y.m",strtotime($selected_date)));
            Context::set('before_url',getUrl('selected_date',date("Ymd",strtotime($selected_date)-60*60*24*30)));
            Context::set('after_url',getUrl('selected_date',date("Ymd",strtotime($selected_date)+60*60*24*30)));
        }

        /**
         * @brief display upgletyle tool statistics popular
         **/
        function dispUpgletyleToolStatisticsPopular(){
            $selected_date = Context::get('selected_date');
            if(!$selected_date){
                $selected_date = date('Ymd');
                Context::set('selected_date',$selected_date);
            }

            $args->sort_index = Context::get('sort_index');
            $args->module_srl = $this->module_srl;
            $args->sort_index = $args->sort_index ? $args->sort_index : 'readed_count';
            $args->order_type = 'desc';
            $args->search_target = 'regdate';
            $args->search_keyword = substr($selected_date,0,6);
            $args->list_count = 10;
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args, false, false);
            Context::set('post_list',$output->data);

            Context::set('disp_selected_date',date("Y.m",strtotime($selected_date)));
            Context::set('before_url',getUrl('selected_date',date("Ymd",strtotime($selected_date)-60*60*24*30)));
            Context::set('after_url',getUrl('selected_date',date("Ymd",strtotime($selected_date)+60*60*24*30)));
        }

		function dispUpgletyleToolPluginList() {

            $oModuleModel = &getModel('module');
			$module_list = FileHandler::readDir(_XE_PATH_.'modules/');

			//Get a only upgletyle plugin (upgletyle_plugin_%s)
			$plugin_list = array();
			foreach($module_list as $val) {
				if(strstr($val, 'upgletyle_plugin'))
				{
					$module_info = $oModuleModel->getModuleInfoXml($val);
					$module_info->part_config = $oModuleModel->getModulePartConfig($val, $this->module_info->module_srl);
					$module_info->activated = $module_info->part_config->activated;
					$module_info->plugin = $val;
					$module_info->thumbnail_path = "./modules/".$val."/conf/thumbnail.gif";
					$plugin_list[] = $module_info;
				}
			}
            Context::set('plugin_list',$plugin_list);
            Context::set('module_info',$this->module_info);

		}

		function dispUpgletyleToolPluginConfig() {

			$plugin = Context::get('plugin');
            $oPluginView = &getView($plugin);
			$config = $oPluginView->dispPluginConfig();

            Context::set('config',$config);
		}

		function dispUpgletyleToolPluginWidgetConfig() {

            $oUpgletyleModel = &getModel('upgletyle');

            $oModuleModel = &getModel('module');
			$module_list = FileHandler::readDir(_XE_PATH_.'modules/');

			//Get a only upgletyle plugin (upgletyle_plugin_%s)
			$plugin_list = array();
			foreach($module_list as $val) {

				//Check it is upgletyle plugin
				if(!strstr($val, 'upgletyle_plugin')) continue;

				//Check it is activated
				$part_config = $oModuleModel->getModulePartConfig($val, $this->module_info->module_srl);
				if(!$part_config->activated) continue;

				$widget_info_xml = $oUpgletyleModel->getWidgetInfoXml($val);
				foreach($widget_info_xml as $k => $v) {
					//Set a position
					$v->plugin = $val;
					$output = $oUpgletyleModel->getUpgletyleWidgetConfig($this->module_info->module_srl, $val, $v->act);
					if($output->data && $output->data->list_order)
					{
						$list_order = $output->data->list_order;
						if($list_order > 0) { 
							$widget_list->top[$list_order] = $v;
						}
						if($list_order < 0) {
							$widget_list->bottom[$list_order] = $v;
						}
					}
					else $widget_list->disabled[] = $v;
				}
			}
			//Array key sort
			if(is_array($widget_list->top) && $widget_list->top)
				krsort($widget_list->top); 
			if(is_array($widget_list->bottom) && $widget_list->bottom)
				krsort($widget_list->bottom);

            Context::set('widget_list',$widget_list);
            Context::set('module_info',$this->module_info);
		}


        function dispUpgletyleToolLayoutConfigSkin() {
            $oModuleModel = &getModel('module');

            $skins = $oModuleModel->getSkins($this->module_path);
            if(count($skins)) {
                foreach($skins as $skin_name => $info) {
                    $large_screenshot = $this->module_path.'skins/'.$skin_name.'/screenshots/large.jpg';
                    if(!file_exists($large_screenshot)) $large_screenshot = $this->module_path.'tpl/img/@large.jpg';
                    $small_screenshot = $this->module_path.'skins/'.$skin_name.'/screenshots/small.jpg';
                    if(!file_exists($small_screenshot)) $small_screenshot = $this->module_path.'tpl/img/@small.jpg';

                    unset($obj);
                    $obj->title = $info->title;
                    $obj->description = $info->description;
                    $_arr_author = array();
                    for($i=0,$c=count($info->author);$i<$c;$i++) {
                        $name =  $info->author[$i]->name;
                        $homepage = $info->author[$i]->homepage;
                        if($homepage) $_arr_author[] = '<a href="'.$homepage.'">'.$name.'</a>';
                        else $_arr_author[] = $name;
                    }
                    $obj->author = implode(',',$_arr_author);
                    $obj->large_screenshot = $large_screenshot;
                    $obj->small_screenshot = $small_screenshot;
                    $obj->date = $info->date;
                    $output[$skin_name] = $obj;
                }
            }
            Context::set('skins', $output);
            Context::set('cur_skin', $output[$this->module_info->skin]);
        }

        function dispUpgletyleToolLayoutConfigMobileSkin() {
            $oModuleModel = &getModel('module');

            $skins = $oModuleModel->getSkins($this->module_path, 'm.skins');
            if(count($skins)) {
                foreach($skins as $skin_name => $info) {
                    $large_screenshot = $this->module_path.'m.skins/'.$skin_name.'/screenshots/large.jpg';
                    if(!file_exists($large_screenshot)) $large_screenshot = $this->module_path.'tpl/img/@large.jpg';
                    $small_screenshot = $this->module_path.'m.skins/'.$skin_name.'/screenshots/small.jpg';
                    if(!file_exists($small_screenshot)) $small_screenshot = $this->module_path.'tpl/img/@small.jpg';

                    unset($obj);
                    $obj->title = $info->title;
                    $obj->description = $info->description;
                    $_arr_author = array();
                    for($i=0,$c=count($info->author);$i<$c;$i++) {
                        $name =  $info->author[$i]->name;
                        $homepage = $info->author[$i]->homepage;
                        if($homepage) $_arr_author[] = '<a href="'.$homepage.'">'.$name.'</a>';
                        else $_arr_author[] = $name;
                    }
                    $obj->author = implode(',',$_arr_author);
                    $obj->large_screenshot = $large_screenshot;
                    $obj->small_screenshot = $small_screenshot;
                    $obj->date = $info->date;
                    $output[$skin_name] = $obj;
                }
            }

			if($this->module_info->mskin == '/USE_DEFAULT/' && $this->module_info->is_mskin_fix == 'N')
			{
            	$site_module_info = Context::get('site_module_info');
				$defaultSkin = $oModuleModel->getModuleDefaultSkin('upgletyle', 'M', $site_module_info->site_srl);
            	Context::set('cur_skin', $output[$defaultSkin]);
			}
			else
			{
            	Context::set('cur_skin', $output[$this->module_info->mskin]);
			}

            Context::set('skins', $output);
        }


        function dispUpgletyleToolLayoutConfigEdit() {
            $oUpgletyleModel = &getModel('upgletyle');
            $skin_path = $oUpgletyleModel->getUpgletylePath($this->module_srl);

            $skin_file_list = $oUpgletyleModel->getUpgletyleUserSkinFileList($this->module_srl);
            $skin_file_content = array();
            foreach($skin_file_list as $file){
				if(preg_match('/^upgletyle/',$file)){
					$skin_file_content[$file] = FileHandler::readFile($skin_path . $file);
				}
            }
            foreach($skin_file_list as $file){
				if(!in_array($file,$skin_file_content)){
					$skin_file_content[$file] = FileHandler::readFile($skin_path . $file);
				}
            }

            Context::set('skin_file_content',$skin_file_content);

            $user_image_path = sprintf("%suser_images/", $oUpgletyleModel->getUpgletylePath($this->module_srl));
            $user_image_list = FileHandler::readDir($user_image_path);
            Context::set('user_image_path',$user_image_path);
            Context::set('user_image_list',$user_image_list);
        }

        function dispUpgletyleToolConfigProfile(){
            $oMemberModel = &getModel('member');
            $member_config = $oMemberModel->getMemberConfig();
            Context::set('profile_image_width', $member_config->profile_image_max_width);
            Context::set('profile_image_height', $member_config->profile_image_max_height);

            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'module_srl';
            $option->content_key_name = 'profile_content';
            $option->allow_fileupload = true;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 500;
            $editor = $oEditorModel->getEditor($this->module_srl, $option);
            Context::set('profile_content_editor', $editor);
        }

        function dispUpgletyleToolConfigInfo(){
            Context::set('langs', Context::loadLangSelected());

            Context::set('time_zone_list', $GLOBALS['time_zone']);
            Context::set('time_zone', $GLOBALS['_time_zone']);
        }

        function dispUpgletyleToolConfigPostwrite(){
            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_config_postwrite.xml');

            $oEditorModel = &getModel('editor');
            $editor_skin_list = $oEditorModel->getEditorSkinList();
            Context::set('editor_skin_list',$editor_skin_list);

            $option->primary_key_name = 'module_srl';
            $option->content_key_name = 'post_prefix';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = false;
            $option->enable_component = false;
            $option->resizable = false;
            $option->height = 200;
            $post_prefix_editor = $oEditorModel->getEditor(0, $option);
            Context::set('post_prefix_editor', $post_prefix_editor);

            $option->primary_key_name = 'module_srl';
            $option->content_key_name = 'post_suffix';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = false;
            $option->enable_component = false;
            $option->resizable = false;
            $option->height = 200;
            $post_suffix_editor = $oEditorModel->getEditor(0, $option);
            Context::set('post_suffix_editor', $post_suffix_editor);
        }

        function dispUpgletyleToolConfigEditorComponents(){
            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;

            $oEditorModel = &getModel('editor');
            $component_list = $oEditorModel->getComponentList(false, $site_srl);

            Context::set('component_list', $component_list);
        }

        function dispUpgletyleToolConfigCommunication(){
            $editor_skin_list = FileHandler::readDir(_XE_PATH_.'modules/editor/skins');
            Context::set('editor_skin_list', $editor_skin_list);

            $oRssModel = &getModel('rss');
            Context::set('rss_config', $oRssModel->getRssModuleConfig($this->module_srl));
        }

        function dispUpgletyleToolConfigBlogApi() {
            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getBlogApiService();
            Context::set('api_services',$output->data);

            $oPublish = $oUpgletyleModel->getPublishObject($this->module_srl);
            Context::set('oPublish', $oPublish);

            $api_srl = Context::get('api_srl');
            if($api_srl) {
                $args->api_srl = $api_srl;
                $args->module_srl = $this->module_srl;
                $output = executeQuery('upgletyle.getApiInfo',$args);
                Context::set('api_info', $output->data);
            }
        }

        function dispUpgletyleToolPostManageBasket(){
            $oDocumentModel = &getModel('document');
            $oDocumentAdminModel = &getAdminModel('document');

            $args->page = Context::get('page');
            if(!$args->page) $args->page = 1;
            Context::set('page',$args->page);

            $args->search_target = Context::get('search_target');
            $args->search_keyword = Context::get('search_keyword');
            $args->module_srl = $this->module_srl;

			$oTrashModel = getModel('trash');
			$output = $oTrashModel->getTrashList($args);

            Context::set('trash_list',$output->data);
            Context::set('page_navigation', $output->page_navigation);

            $category_list = $oDocumentModel->getCategoryList($this->module_srl);
            Context::set('category_list', $category_list);

            foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);
            Context::set('search_option', $search_option);
        }

        function dispUpgletyleToolConfigAddon() {
            $oAddonModel = &getAdminModel('addon');
            $oAdminView= &getAdminView('admin');
            $addon_list = $oAddonModel->getAddonList($this->site_srl);
            Context::set('addon_list', $addon_list);
        }

        function dispUpgletyleToolConfigData() {
			$logged_info = Context::get('logged_info');
			if($logged_info && $logged_info->is_admin=='Y'){
				Context::addJsFilter($this->module_path.'tpl/filter', 'export_upgletyle.xml');
			}else{
				Context::addJsFilter($this->module_path.'tpl/filter', 'request_export_upgletyle.xml');
			}

			$args->site_srl = $this->site_srl;
			$output = executeQuery('upgletyle.getExport',$args);
			Context::set('export',$output->data);
        }

        function dispUpgletyleToolConfigChangePassword(){
            Context::addJsFilter($this->module_path.'tpl/filter', 'modify_password.xml');
        }

        /**
         * @brief Upgletyle home
         **/
    function dispUpgletyle()
        {

        	//$this->module_info->skin
        	$oModuleModel = &getModel('module');
        	$skins = $oModuleModel->getSkins($this->module_path);
        	$current_skin = $skins[$this->module_info->skin];
        	if(isset($current_skin->extra_vars)){
	        	foreach($current_skin->extra_vars as $extra_var){
	        		if($extra_var->name == 'content_type') $current_content_type = $extra_var->title;
	        	}
        	}
        	if($current_content_type == 'multiple_posts'){
        		$this->dispMultiPostUpgletyle();
        	} else {
        		$oDocumentModel = &getModel('document');
	            $var = Context::getRequestVars();

	            // set category
	            $category_list = $oDocumentModel->getCategoryList($this->module_srl);
	            Context::set('category_list', $category_list);
	
	            if($var->preview == 'Y'){
	            	  Context::set('upgletyle_mode', 'content');
	            	  Context::set('textyle_mode', 'content'); //for textyle skins
	            	  $prev_document = $oDocumentModel->getDocument($var->document_srl);
	            	  $document_list[] = $prev_document;
	            	  Context::set('document_list', $document_list);
	            	  return;
	            }
	        	$oUpgletyleModel = &getModel('upgletyle');
	            $oUpgletyleController = &getController('upgletyle');
	            
	            $document_srl = Context::get('document_srl');
	            $page = Context::get('page');
	            $page = $page>0 ? $page : 1;
	            Context::set('page',$page);
	
	            if($document_srl) {
	                $oDocument = $oDocumentModel->getDocument($document_srl,false,false);
	                if($oDocument->isExists()) {
	                    if($oDocument->get('module_srl')!=$this->module_info->module_srl ) return $this->stop('msg_invalid_request');
	
	                    Context::setBrowserTitle($this->upgletyle->get('browser_title') . ' »  ' . $oDocument->getTitleText());
	
	                    // meta keywords category + tag
	                    $tag_array = $oDocument->get('tag_list');
	                    if($tag_array) {
	                        $tag = htmlspecialchars(join(', ',$tag_array));
	                    } else {
	                        $tag = '';
	                    }
	                    $category_srl = $oDocument->get('category_srl');
	                    if($tag && $category_srl >0) $tag = $category_list[$category_srl]->title .', ' . $tag;
						Context::addMetaTag('keywords',$tag);
	
	                    if($this->grant->manager) $oDocument->setGrant();
	

	                } else {
	                    Context::set('document_srl','',true);
	                    //$this->alertMessage('msg_not_founded');
	                }
	            } else {
	                $oDocument = $oDocumentModel->getDocument(0,false,false);
	            }
	            Context::set('oDocument', $oDocument);
	
	            $args->module_srl = $this->module_srl;
	            $args->category_srl = Context::get('category');
	            $args->page = $page;
	            $args->page_count = 10;
	            $args->search_target = Context::get('search_target');
	            $args->search_keyword = Context::get('search_keyword');
	            $args->sort_index = Context::get('sort_index');
	            $args->order_type = Context::get('order_type');
	            if(!in_array($args->sort_index, $this->order_target)) $args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
	            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';
	
	            if($oDocument->isExists()) {
	                $document_list[] = $oDocument;
	                Context::set('none_navigation', true);
	            } else {
	                $args->list_count = $this->upgletyle->getPostListCount();
	                if($args->search_target && $args->search_keyword || $args->category_srl) $args->list_count=$this->upgletyle->getCategoryListCount();
	                $output = $oDocumentModel->getDocumentList($args, false, false);
	                $document_list = $output->data;
	                Context::set('page_navigation', $output->page_navigation);
	            }
	
	            if(is_array($document_list)) $_key = array_keys($document_list);
	            if(count($_key)==1) {
	                $_srl = array_pop($_key);
	                $doc = $document_list[$_srl];
	                if($doc->document_srl) {
	                    $args->document_srl = $doc->document_srl;
	                    $output = executeQuery('upgletyle.getNextDocument', $args);
	                    if($output->data->document_srl) Context::set('prev_document', new documentItem($output->data->document_srl,false));
	                    $output = executeQuery('upgletyle.getPrevDocument', $args);
	                    if($output->data->document_srl) Context::set('next_document', new documentItem($output->data->document_srl,false));
	
	                    if(!$doc->isSecret() || $doc->isGranted()) $doc->updateReadedCount();
	
	                    $oUpgletyleController->insertReferer($doc);
	                }
	            }
				//Load a widget
				$output = $oUpgletyleModel->getUpgletyleWidget($this->module_info->module_srl);
	            if(!$output->toBool()) return $output;
				$widget_list = $output->data;

				foreach($document_list as $val)
				{
					$content = $val->get('content');
					foreach($widget_list as $widget)
					{
						$oUpgletyleController = &getController('upgletyle');
						$output = $oUpgletyleController->widgetCall($widget->plugin,$widget->type,$widget->act, $val);
			            if(!$output->toBool()) continue;
	
						if($widget->list_order > 0) {
							$content = $output->get('compiled_widget').$content;
						}
						elseif($widget->list_order < 0) {
							$content .= $output->get('compiled_widget');
						}
					}
					$val->add('content',$content);
				}
	            Context::set('document_list', $document_list);
	
	            if(!$args->category_srl && !$args->search_keyword) {
	                if($oDocument->isExists()) $mode = 'content';
	                else $mode = $this->upgletyle->getPostStyle();
	            } else {
	                if($oDocument->isExists()) $mode = 'content';
	                else $mode = 'list';
	            }
	            Context::set('upgletyle_mode', $mode);
	            Context::set('textyle_mode', $mode); //for textyle skins
	
	            $category_list = $oDocumentModel->getCategoryList($this->module_srl);
	            if($args->category_srl) Context::set('selected_category', $category_list[$args->category_srl]->title);
	
	            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');
	            Context::addJsFilter($this->module_path.'tpl/filter', 'input_password.xml');
	            Context::addJsFilter($this->module_path.'tpl/filter', 'input_password_for_modify_comment.xml');
        	}
        }
        
        function dispMultiPostUpgletyle(){
        	// $document_srl is obtained only at Comment Reply and Comment Modify.
            $document_srl = Context::get('document_srl');
            $oDocumentModel = &getModel('document');
            $category = Context::get('category');

            if ($document_srl)
            {
                $oDocument = $oDocumentModel->getDocument($document_srl, false, false);
                // If document exists, then this is a comment posting case,
                // so we don't need to perform all other operations
                if ($oDocument->isExists())
                {
                    // If this document doesn't belong to this blog module,
                    // ignore it.
                    if ($oDocument->get('module_srl') != $this->module_info->module_srl)
                    {
                        return $this->stop('msg_invalid_request');
                    }
                }
                else{
                    Context::set('document_srl','',true);
                    return $this->stop('msg_invalid_request');
                }
            }
            else{
                $alias_title = Context::get('alias_title');

                if ($alias_title)
                {
                    $query_arguments->alias_title = "/" . $alias_title . "/";
                    $output = executeQuery('upgletyle.getDocumentSrlByAlias', $query_arguments);

                    if($output->data)
                    {
                        $document_srl = $output->data->document_srl;
                        $oDocument = $oDocumentModel->getDocument($document_srl, false, false);

                        if ($oDocument->isExists())
                        {
                            $oDocument->alias_title = $category . '/' . $alias_title . '/';
                        }
                    }
                }
            }
            // set the page
            $page = Context::get('page');
            $page = $page > 0 ? $page : 1;
            Context::set('page', $page);
            // get a list of categories of upgletyle
            $category_list = $oDocumentModel->getCategoryList($this->module_srl);

            Context::set('module_name', $this->upgletyle->domain);
            Context::set('category_list', $category_list);

            // Wanted Post List
            $args->module_srl = $this->module_srl;
            $args->page_count = 10;

            $args->sort_index = Context::get('sort_index');
            $args->order_type = Context::get('order_type');

            if (!in_array($args->sort_index, $this->order_target))
            {
                $args->sort_index = $this->module_info->order_target ? $this->module_info->order_target : 'list_order';
            }

            if (!in_array($args->order_type, array('asc','desc')))
            {
                $args->order_type = $this->module_info->order_type ? $this->module_info->order_type : 'asc';
            }

            $recentTags = array();

            // ì„ íƒ�ë�œ ê¸€ì�´ í•˜ë‚˜ë�¼ë�„ ê¸€ ëª©ë¡�ìœ¼ë¡œ í˜•ì„±
            if ($oDocument && $oDocument->isExists())
            {
                $mode = 'content';
                // set the browser title
                Context::setBrowserTitle($oDocument->getTitle() . ' | ' . $this->upgletyle->get('browser_title'));
                // set meta keywords category + all tags of the document
                $docTags = $oDocument->get('tags');
                $category_srl = $oDocument->get('category_srl');

                if ($category_srl)
                {
                    $tags = $category_list[$category_srl]->title;
                }

                if ($docTags)
                {
                    $docTagsArray = $oDocument->get('tag_list');

                    foreach($docTagsArray as $tag)
                    {
                        ++$recentTags[$tag];
                    }

                    $tags .= ($tags ? ',' : '') . $docTags;
                }

                // ì„ íƒ�ë�œ ê¸€ì�´ í•˜ë‚˜ì�¼ ê²½ìš° ì�´ì „/ ë‹¤ì�Œ íŽ˜ì�´ì§€ êµ¬í•¨ + ì¡°íšŒìˆ˜ ì¦�ê°€ + referer ê¸°ë¡�
                $oDocument->updateReadedCount();

                // referer ë‚¨ê¹€
                $oUpgletyleController = &getController('upgletyle');
                $oUpgletyleController->insertReferer($oDocument);

                // ê´€ë¦¬ ê¶Œí•œì�´ ìžˆë‹¤ë©´ ê¶Œí•œì�„ ë¶€ì—¬
                if($this->grant->manager) $oDocument->setGrant();
				$oDocument->variables['relative_date'] = $this->zdateRelative($oDocument->getRegdateTime());
                $document_list[] = $oDocument;
                $nr_documents = count($document_list);
                Context::set('nr_documents',$nr_documents);
                if($nr_documents == 1) {
                	$_comment_list = $oDocument->getComments();
                	if(isset($_comment_list)){
	                	foreach($_comment_list as $comment){
	                		$comment->variables['relativeDate'] = $this->zdateRelative($comment->getRegdateTime());
	                	}
                	}
                	Context::set('_comment_list', $_comment_list);
                }
                Context::set('document_list', $document_list);
            }
            else{
                $mode = $this->upgletyle->getPostStyle();
                // Check if the search is within a specific tag
                $search_target = Context::get('search_target');

                if ($search_target == 'tags')
                {
                    $args->tag = Context::get('search_keyword');
                }

                // Get the category
                $args->category_srl = urldecode($category);

                if ($args->category_srl && !is_numeric($args->category_srl))
                {
                    $arguments->category_title = $args->category_srl;
                    $arguments->module_srl = $this->module_srl;
                    $output = executeQuery('upgletyle.getCategorySrl', $arguments);

                    if($output->data)
                    {
                        $args->category_srl = $output->data->category_srl;
                        $args->categories[] = $args->category_srl;
                        if($category_list[$args->category_srl]->child_count)
                        foreach($category_list[$args->category_srl]->childs as $child){
                        	$args->categories[] = $child;
                        }
                        $tags = $category_list[$args->category_srl]->title;
                        // set the browser title for this category
                        Context::setBrowserTitle($tags . ' | ' . $this->upgletyle->get('browser_title'));
                    }
                }
                else{
                    $args->category_srl = Context::get('category_srl');
                }
                // If there is such a category
                if ($args->category_srl)
                {
                    Context::set('selected_category', $category_list[$args->category_srl]->title);
                }
                // Get the most popular blog (based on readed_count column) for the past month.
                // By default it return 1 top viewed document.
                // It is displayed only on the front page of the blog, i.e. on the first page.
                elseif ($page == 1 && !isset($args->tag))
                {
                    $args->module_srl = $this->module_srl;
                    $args->start_date = date('YmdHis', strtotime("-1 month"));
                    $args->end_date = date('YmdHis');
                    $args->sort_index = 'readed_count';
                    $args->page = 1;
                    $args->list_count = 3;

                    $mostPopularBlogs = $this->getDocumentItems('upgletyle.getTopViewDocumentsInDateRange', $args);
                    foreach($mostPopularBlogs->data as $popularBlog){
                    	$popularBlog->variables['relative_date'] = $this->zdateRelative($popularBlog->getRegdateTime());
                    	if($popularBlog->get('alias_title')){
                    		$popularBlog->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/entry/'.$popularBlog->get('alias_title');
                    	}else{
                    		$popularBlog->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/'.$popularBlog->get('document_srl');
                    	}
                    }
                    Context::set('mostPopularBlogs', $mostPopularBlogs->data);

                    unset($args->start_date);
                    unset($args->end_date);
                    $args->list_count = 5;
                    $args->page = $page;

                    $allPopularBlogs = $this->getDocumentItems('upgletyle.getAllTimeTopViewDocuments', $args);
                	foreach($allPopularBlogs->data as $popularBlog){
                    	$popularBlog->variables['relative_date'] = $this->zdateRelative($popularBlog->getRegdateTime());
                		if($popularBlog->get('alias_title')){
                    		$popularBlog->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/entry/'.$popularBlog->get('alias_title');
                    	}else{
                    		$popularBlog->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/'.$popularBlog->get('document_srl');
                    	}
                    }
                    Context::set('allPopularBlogs', $allPopularBlogs->data);
                }

                // Get a list of latest posts
                $args->module_srl = $this->module_srl;
                $args->list_count = $this->upgletyle->getPostListCount();
                $args->sort_index = 'list_order';
                $args->page = $page;
                $args->page_count = 10;
                
                $latestBlogs = $this->getDocumentItems('upgletyle.getPosts', $args);

                Context::set('latestBlogs', $latestBlogs->data);
                Context::set('page_navigation', $latestBlogs->page_navigation);
				if(isset($latestBlogs->data)){
	                foreach($latestBlogs->data as $document)
	                {
	                    $docTags = $document->get('tag_list');
						if(isset($docTags)){
		                    foreach($docTags as $tag)
		                    {
		                        $recentTags[$tag]++;
		                    }
						}
						$document->variables['relative_date'] = $this->zdateRelative($document->getRegdateTime());
	                		if($document->get('alias_title')){
	                    		$document->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/entry/'.$document->get('alias_title');
	                    	}else{
	                    		$document->variables['url'] = getSiteUrl().$this->upgletyle->domain.'/'.$document->get('document_srl');
	                    	}
	                }
				}
                arsort($recentTags);
                $tags .= ',' . implode(',', array_keys($recentTags));
            }

			Context::addMetaTag('keywords',$tags);
            Context::addJsFilter($this->module_path . 'skins/' . $this->module_info->skin . '/filter', 'insert_comment.xml');

            Context::set('upgletyle_mode', $mode);
            Context::set('textyle_mode', $mode);//for textyle skins

            Context::set('recentTags', $recentTags);

            Context::set('module_path', $this->module_path);
        }
        
	    function zdateRelative($date)
	    {
	        $diff = time() - $date;
	
	        if ($diff < 60){
	            return sprintf($diff > 1 ? Context::getLang('seconds_ago') : Context::getLang('second_ago'), $diff);
	        }
	
	        $diff = floor($diff/60);
	
	        if ($diff < 60){
	            return sprintf($diff > 1 ? Context::getLang('minutes_ago') : Context::getLang('minute_ago'), $diff);
	        }
	
	        $diff = floor($diff/60);
	
	        if ($diff < 24){
	            return sprintf($diff > 1 ? Context::getLang('hours_ago') : Context::getLang('hour_ago'), $diff);
	        }
	
	        $diff = floor($diff/24);
	
	        if ($diff < 7){
	            return sprintf($diff > 1 ? Context::getLang('days_ago') : Context::getLang('day_ago'), $diff);
	        }
	
	        if ($diff < 30)
	        {
	            $diff = floor($diff / 7);
	
	            return sprintf($diff > 1 ? Context::getLang('weeks_ago') : Context::getLang('week_ago'), $diff);
	        }
	
	        $diff = floor($diff/30);
	
	        if ($diff < 12){
	            return sprintf($diff > 1 ? Context::getLang('months_ago') : Context::getLang('month_ago'), $diff);
	        }
	
	        $diff = floor($diff/12);
	
	        return sprintf($diff > 1 ? Context::getLang('years_ago') : Context::getLang('year_ago'), $diff);
	    }
	    
        private function getDocumentItems($query, $args)
        {
            $documents = executeQuery($query, $args);

            if ($documents->data)
            {
                if (!is_array($documents->data))
                {
                    $documents->data = array($documents->data);
                }

                foreach($documents->data as $key => &$attribute)
                {
                    $document_srl = $attribute->document_srl;

                    $oDocumentMostPopular = null;
                    $oDocumentMostPopular = new documentItem();
                    $oDocumentMostPopular->setAttribute($attribute, false);
                    $attribute = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
                }
            }

            return $documents;
        }

        function dispCommentEditor()
        {
            $document_srl = Context::get('document_srl');
            //$logged_info = Context::get('logged_info');
            //$logged_info->group_list[1] = 1;
            //Context::set('logged_info',$logged_info);

            $oDocumentModel = &getModel("document");
            $oDocument = $oDocumentModel->getDocument($document_srl);

            if (!$oDocument->isExists())
            {
                return new Object(-1, 'msg_invalid_request');
            }

            if (!$oDocument->allowComment())
            {
                return new Object(-1, 'comments_disabled');
            }

            Context::set('oDocument', $oDocument);

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($oDocument->get('module_srl'));

            Context::set("module_info", $module_info);

            $module_path = './modules/' . $module_info->module . '/';
            $skin_path = $module_path . 'skins/' . $module_info->skin . '/';

            if(!$module_info->skin || !is_dir($skin_path))
            {
                $skin_path = $module_path . 'skins/multiPost/';
            }

            $oTemplateHandler = &TemplateHandler::getInstance();
			$html = base64_encode($oTemplateHandler->compile($skin_path, 'comment_form.html'));
            $this->add('html', $html);
            $this->add('message_type', 'info');
        }

        function dispModifyComment()
        {
            // allow only logged in users to comment.
            if (!Context::get('is_logged'))
            {
                return new Object(-1, 'login_to_modify_comment');
            }

            $comment_srl = Context::get('comment_srl');

            // ì§€ì •ë�œ ëŒ“ê¸€ì�´ ì—†ë‹¤ë©´ ì˜¤ë¥˜
            if (!$comment_srl)
            {
                return new Object(-1, 'msg_invalid_request');
            }

            // í•´ë‹¹ ëŒ“ê¸€ë¥¼ ì°¾ì•„ë³¸ë‹¤
            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // ëŒ“ê¸€ì�´ ì—†ë‹¤ë©´ ì˜¤ë¥˜
            if (!$oComment->isExists())
            {
                return $this->dispWikiMessage('msg_invalid_request');
            }

            // ê¸€ì�„ ìˆ˜ì •í•˜ë ¤ê³  í•  ê²½ìš° ê¶Œí•œì�´ ì—†ëŠ” ê²½ìš° ë¹„ë°€ë²ˆí˜¸ ìž…ë ¥í™”ë©´ìœ¼ë¡œ
            if (!$oComment->isGranted())
            {
                return $this->setTemplateFile('input_password_form');
            }

            // í•„ìš”í•œ ì •ë³´ë“¤ ì„¸íŒ…
            Context::set('oComment', $oComment);

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($oComment->get('module_srl'));

            if (!$oComment->isGranted())
            {
                return new Object(-1, 'no_rights_to_modify_comment');
            }

            Context::set("module_info", $module_info);

            $module_path = './modules/' . $module_info->module . '/';
            $skin_path = $module_path . 'skins/' . $module_info->skin . '/';

            if(!$module_info->skin || !is_dir($skin_path))
            {
                $skin_path = $module_path . 'skins/multiPost/';
            }

            $oTemplateHandler = &TemplateHandler::getInstance();
			
            $html = base64_encode($oTemplateHandler->compile($skin_path, 'comment_form.html'));
            $this->add('html', $html);

            $this->add('html', $html);
            $this->add('comment_srl', $oComment->comment_srl);
        }

        function dispReplyComment()
        {
            // ëª©ë¡� êµ¬í˜„ì—� í•„ìš”í•œ ë³€ìˆ˜ë“¤ì�„ ê°€ì ¸ì˜¨ë‹¤
            $parent_srl = Context::get('comment_srl');

            // ì§€ì •ë�œ ì›� ëŒ“ê¸€ì�´ ì—†ë‹¤ë©´ ì˜¤ë¥˜
            if (!$parent_srl)
            {
                return new Object(-1, 'msg_invalid_request');
            }

            // í•´ë‹¹ ëŒ“ê¸€ë¥¼ ì°¾ì•„ë³¸ë‹¤
            $oCommentModel = &getModel('comment');
            $oSourceComment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // ëŒ“ê¸€ì�´ ì—†ë‹¤ë©´ ì˜¤ë¥˜
            if (!$oSourceComment->isExists())
            {
                return new Object(-1, 'msg_invalid_request');
            }

            $oDocumentModel = &getModel("document");
            $oDocument = $oDocumentModel->getDocument($oSourceComment->get('document_srl'));

            if (!$oDocument->isExists())
            {
                return new Object(-1, 'msg_invalid_request');
            }

            if (!$oDocument->allowComment())
            {
                return new Object(-1, 'comments_disabled');
            }

            // ëŒ€ìƒ� ëŒ“ê¸€ì�„ ìƒ�ì„±
            $oComment = $oCommentModel->getComment();
            $oComment->add('parent_srl', $parent_srl);
            $oComment->add('document_srl', $oSourceComment->get('document_srl'));
            $oComment->add('module_srl', $this->module_srl);

            // í•„ìš”í•œ ì •ë³´ë“¤ ì„¸íŒ…
            Context::set('oComment', $oComment);

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($oDocument->get('module_srl'));

            Context::set("module_info", $module_info);

            $module_path = './modules/' . $module_info->module . '/';
            $skin_path = $module_path . 'skins/' . $module_info->skin . '/';

            if(!$module_info->skin || !is_dir($skin_path))
            {
                $skin_path = $module_path . 'skins/multiPost/';
            }

            $oTemplateHandler = &TemplateHandler::getInstance();

            $html = base64_encode($oTemplateHandler->compile($skin_path, 'comment_form.html'));
            $this->add('html', $html);
            $this->add('parent_srl', $parent_srl);
        }
        function dispUpgletyleCommentReply(){
            $parent_srl = Context::get('comment_srl');
            $document_srl = Context::get('document_srl');

            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            $oCommentModel = &getModel('comment');
            $oSourceComment = $oCommentModel->getComment($parent_srl);

            if(!$oSourceComment->isExists()) return $this->dispUpgletyleMessage('msg_invalid_request');

            if($document_srl && $oSourceComment->get('document_srl') != $document_srl) return $this->dispUpgletyleMessage('msg_invalid_request');

            $oComment = $oCommentModel->getComment(0);
            $oComment->add('parent_srl', $parent_srl);
            $oComment->add('document_srl', $oSourceComment->get('document_srl'));

            Context::set('oSourceComment',$oSourceComment);
            Context::set('oComment',$oComment);
            Context::set('module_srl',$this->upgletyle->module_srl);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');
            Context::set('upgletyle_mode','comment_form');
            Context::set('textyle_mode','comment_form'); //for textyle skins
        }

        function dispUpgletyleCommentModify(){
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            if(!$oComment->isExists()) return $this->dispBoardMessage('msg_invalid_request');

            Context::set('oSourceComment', $oCommentModel->getComment());
            Context::set('oComment', $oComment);
            Context::set('upgletyle_mode','comment_form');
            Context::set('textyle_mode','comment_form'); //for textyle skins

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');
        }

        /**
         * @brief Upgletyle guestbook
         **/
        function dispUpgletyleGuestbook(){
            $reply = Context::get('replay');
            $modify = Context::get('modify');
            $page = Context::get('page');
            $page = $page ? $page : 1;
            Context::set('page',$page);

            $args->module_srl = $this->module_srl;
            $args->search_text = Context::get('search_text');
            $args->page = $page;
			$args->list_count = $this->upgletyle->getGuestbookListCount();

            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getUpgletyleGuestbookList($args);
            Context::set('guestbook_list',$output->data);
            Context::set('page_navigation', $output->page_navigation);

            // editor
            $oEditorModel = &getModel('editor');
            if($reply) $option->primary_key_name = 'parent_srl';
            else $option->primary_key_name = 'upgletyle_guestbook_srl';

            $option->skin = $this->upgletyle->get('guestbook_editor_skin');
            $option->colorset = $this->upgletyle->get('guestbook_editor_colorset');
            $option->content_key_name = 'content';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = false;
            $option->enable_component = false;
            $option->resizable = false;
            $option->height = 100;
            $option->disable_html = true;
            $editor = $oEditorModel->getEditor(0, $option);
            Context::set('editor', $editor);
            Context::set('upgletyle_mode','guestbook');
            Context::set('textyle_mode','guestbook'); //for textyle skins

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_guestbook.xml');
            Context::addJsFilter($this->module_path.'tpl/filter', 'input_password_for_guestbook.xml');
            Context::addJsFilter($this->module_path.'tpl/filter', 'input_password_for_delete_guestbook.xml');
            Context::addJsFilter($this->module_path.'tpl/filter', 'input_password_for_modify_guestbook.xml');
        }

        /**
         * @brief Upgletyle Profile
         **/
        function dispUpgletyleProfile(){
            $profile_content = $this->upgletyle->getProfileContent();
            Context::set('profile_content',$profile_content);
            Context::set('upgletyle_mode','profile');
            Context::set('textyle_mode','profile'); //for textyle skins
        }

        /**
         * @brief Upgletyle tag
         **/
        function dispUpgletyleTag() {
            $oTagModel = &getModel('tag');
            $oModuleModel =&getModel('module');

            $obj->module_srl = $this->module_srl;
            $obj->list_count = 10000;
            $output = $oTagModel->getTagList($obj);

            if(count($output->data)) {
                $numbers = array_keys($output->data);
                shuffle($numbers);

                if(count($output->data)) {
                    foreach($numbers as $k => $v) {
                        $tag_list[] = $output->data[$v];
                    }
                }
            }
            $site_admin_list = $oModuleModel->getSiteAdmin($this->module_info->site_srl);
            foreach($site_admin_list as $admin){
            	$obj->module_srl = $admin->member_srl;
            	$output = $oTagModel->getTagList($obj);
	            if(count($output->data)) {
	                $numbers = array_keys($output->data);
	                shuffle($numbers);
	
	                if(count($output->data)) {
	                    foreach($numbers as $k => $v) {
	                        $tag_list[] = $output->data[$v];
	                    }
	                }
	            }
            }
            Context::set('tag_list', $tag_list);
            Context::set('upgletyle_mode','tags');
            Context::set('textyle_mode','tags'); //for textyle skins

        }


        function dispUpgletyleContentTagSearch(){
            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->upgletyle->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oUpgletyleModel = &getModel('upgletyle');
            Context::set('search_result', $oUpgletyleModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oUpgletyleModel->getContentList($module_srl,'tag',$keyword, $page, 10);
                Context::set('content_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            $this->setTemplateFile('search');
        }

        function dispUpgletyleContentSearch(){
            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->upgletyle->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oUpgletyleModel = &getModel('upgletyle');

            Context::set('search_result', $oUpgletyleModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oUpgletyleModel->getContentList($module_srl,'content',$keyword, $page, 10);
                Context::set('content_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            $this->setTemplateFile('search');
        }

        function dispUpgletyleTagSearch(){
            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->upgletyle->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oUpgletyleModel = &getModel('upgletyle');

            Context::set('search_result', $oUpgletyleModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oUpgletyleModel->getUpgletyleTagList($keyword, $page, 10);
                Context::set('upgletyle_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            $this->setTemplateFile('search_upgletyle');
        }

        function dispReplyList(){
            $page = Context::get('page');
            $document_srl = Context::get('document_srl');
            $oUpgletyleModel = &getModel('upgletyle');
            $output = $oUpgletyleModel->getReplyList($document_srl,$page);
            Context::set('reply_list',$output->data);
        }

        function dispUpgletyleMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

        function dispUpgletyleToolExtraMenuList(){
            $oUpgletyleModel = &getModel('upgletyle');
            $config = $oUpgletyleModel->getModulePartConfig($this->module_srl);
            Context::set('config',$config);

            $args->site_srl = $this->site_srl;
            $output = executeQueryArray('upgletyle.getExtraMenus',$args);
            if(!$output->toBool()) return $output;
            Context::set('extra_menu_list',$output);

        }
        
        function dispUpgletyleToolExtraMenuModuleInsert(){
            $menu_mid = Context::get('menu_mid');
			if($menu_mid){
				$oModuleModel = &getModel('module');
				$module_info = $oModuleModel->getModuleInfoByMid($menu_mid,$this->site_srl);
				if(!$module_info) return new Object(-1,'msg_invalid_request');

				$args->module_srl = $module_info->module_srl;
				$output = executeQuery('upgletyle.getExtraMenu',$args);
				if($output->data){
					$selected_extra_menu = $output->data;
				}
			}
			if($selected_extra_menu){
				Context::set('selected_extra_menu',$selected_extra_menu);
				Context::addJsFilter($this->module_path.'tpl/filter', 'modify_extra_menu.xml');
			}else{
				Context::addJsFilter($this->module_path.'tpl/filter', 'insert_extra_menu.xml');
			}
			$oUpgletyleModel = &getModel('upgletyle');
			$config = $oUpgletyleModel->getModulePartConfig($this->module_srl);
			Context::set('config',$config);

			$used_extra_menu_count = array();
			$args->site_srl = $this->site_srl;
			$output = executeQueryArray('upgletyle.getExtraMenus',$args);

			if($output->data){
				foreach($output->data as $k => $menu){
					if($config->allow_service[$menu->module]){
						$used_extra_menu_count[$menu->module] += 1;
					}
				}
			}

			Context::set('used_extra_menu_count',$used_extra_menu_count);
        }
        
        function dispUpgletyleToolExtraMenuInsert(){
            // set filter
            $menu_mid = Context::get('menu_mid');
            if($menu_mid){
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByMid($menu_mid,$this->site_srl);
                if(!$module_info) return new Object(-1,'msg_invalid_request');
                
                $oWidgetController = &getController('widget');
                $buff = trim($module_info->content);
                $oXmlParser = new XmlParser();
                $xml_doc = $oXmlParser->parse(trim($buff));
                $document_srl = $xml_doc->img->attrs->document_srl;
                $args->module_srl = $module_info->module_srl;
                $output = executeQuery('upgletyle.getExtraMenu',$args);
                if($output->data){
                   $selected_extra_menu = $output->data;
                }
            }
            if($selected_extra_menu){
                Context::set('selected_extra_menu',$selected_extra_menu);
                Context::addJsFilter($this->module_path.'tpl/filter', 'modify_extra_menu.xml');
            }else{
                Context::addJsFilter($this->module_path.'tpl/filter', 'insert_extra_menu.xml');
            }
            

            $oDocumentModel = &getModel('document');
            $material_srl = Context::get('material_srl');

            if($document_srl){
                    $oDocument = $oDocumentModel->getDocument($document_srl,false,false);
            }else{
                    $document_srl=0;
                    $oDocument = $oDocumentModel->getDocument(0);
                    if($material_srl){
                            $oMaterialModel = &getModel('material');
                            $output = $oMaterialModel->getMaterial($material_srl);
                            if($output->data){
                                    $material_content = $output->data[0]->content;
                                    Context::set('material_content',$material_content);
                            }
                    }

            }

            $oEditorModel = &getModel('editor');
            $option->skin = $this->upgletyle->getPostEditorSkin();
            $option->primary_key_name = 'document_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = true;
            $option->enable_autosave = true;
            $option->enable_default_component = true;
            $option->enable_component = $option->skin =='dreditor' ? false : true;
            $option->resizable = true;
            $option->height = 500;
            $option->content_font = $this->upgletyle->getFontFamily();
            $option->content_font_size = $this->upgletyle->getFontSize();
            $editor = $oEditorModel->getEditor($document_srl, $option);
            Context::set('editor', $editor);
            Context::set('editor_skin', $option->skin);

            if($oDocument->get('module_srl') != $this->module_srl && !$document_srl){
                    Context::set('from_saved',true);
            }

            Context::set('oDocument', $oDocument);
        }

    }
?>
