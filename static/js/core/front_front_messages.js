ips.templates.set('messages.view.placeholder'," <div class='ipsEmpty'>	<i class='fa-solid fa-inbox i-opacity_2'></i>	<p class='i-margin-top_1'>{{#lang}}no_message_selected{{/lang}}</p></div>");ips.templates.set('messages.main.folderMenu',"<li class='ipsMenu_item' data-ipsMenuValue='{{key}}'><a href='#'><span data-role='folderName'>{{name}}</span> <span class='ipsMenu_itemCount'>{{count}}</span></a></li>");;
;(function($,_){"use strict";ips.controller.register('core.front.messages.view',{_currentMessageID:null,_currentPage:1,initialize(){this.on('paginationClicked paginationJump',this.paginationClicked);this.on('addToCommentFeed',this.addToCommentFeed);this.on('deletedComment.comment',this.deleteComment);this.on(document,'menuItemSelected','#elConvoMove',this.moveConversation);this.on(document,'click','[data-action="deleteConversation"]',this.deleteConversation);this.on('menuOpened',"[data-action='inviteUsers']",this.inviteMenuOpened);this.on(document,'menuItemSelected','[data-role="userActions"]',this.userAction);this.on('submit','[data-role="addUser"]',this.addUsersSubmit);this.on(document,'selectedMessage.messages',this.selectedMessage);this.on(document,'setInitialMessage.messages',this.setInitialMessage);this.on(document,'getFolder.messages',this.getFolder);this.on(document,'loadMessageLoading.messages',this.loadMessageLoading);this.on(document,'loadMessageDone.messages',this.loadMessageDone);this.on(document,'deleteMessageDone.messages',this.deleteMessageDone);this.on(document,'blockUserDone.messages',this.blockUserDone);this.on(document,'addUserDone.messages',this.addUserDone);this.on(document,'addUserError.messages',this.addUserError);this.on(window,'historychange:messages',this.stateChange);this.setup();},setup(){if(this.scope.attr('data-current-id')){this._currentMessageID=this.scope.attr('data-current-id');}},addToCommentFeed(e,data){if(data.totalItems){this.trigger('updateReplyCount.messages',{messageID:this._currentMessageID,count:data.totalItems});}},addUserError(e,data){if(data.error){ips.ui.alert.show({type:'alert',icon:'warn',message:data.error,callbacks:{}});}},addUserDone(e,data){if(data.id!==this._currentMessageID){return;}
if(data.error){ips.ui.alert.show({type:'alert',icon:'warn',message:data.error,callbacks:{}});return;}
const numberMembers=_.size(data.members);if(data.members&&numberMembers){for(const i in data.members){const participant=this.scope.find('.cMessage_members').find('[data-participant="'+i+'"]');Debug.log('Ajax response:');Debug.log(data.members[i]);if(participant.length){participant.replaceWith(data.members[i]);}else{this.scope.find('.cMessage_members [data-role="addUserItem"]').before(data.members[i]);}}}
let message=ips.getString('messageUserAdded');if(numberMembers>1){message=ips.pluralize(ips.getString('messageUsersAdded'),numberMembers);}
ips.ui.flashMsg.show(message);if(data.failed&&parseInt(data.failed)>0){ips.ui.flashMsg.show(ips.getString('messageNotAllUsers'));}
this.scope.find('#elInviteMember'+this._currentMessageID).trigger('closeMenu');var autocomplete=ips.ui.autocomplete.getObj(this.scope.find('input[name="member_names"]'));autocomplete.removeAll();},inviteMenuOpened(e){this.scope.find('[data-role="addUser"] input[type="text"][id$="dummyInput"]').focus();},addUsersSubmit(e){e.preventDefault();const names=$(e.currentTarget).find('[name="member_names"]').val();this.trigger('addUser.messages',{id:this._currentMessageID,names:names});},blockUserDone(e,data){if(data.id!=this._currentMessageID){return;}
var participant=this.scope.find('.cMessage_members').find('[data-participant="'+data.member+'"]');participant.replaceWith(data.response);ips.ui.flashMsg.show(ips.getString('messageRemovedUser'));},userAction(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
var userID=$(data.triggerElem).closest('[data-participant]').attr('data-participant');switch(data.selectedItemID){case'block':this.trigger('blockUser.messages',{member:userID,id:this._currentMessageID});break;case'unblock':this.trigger('addUser.messages',{member:userID,id:this._currentMessageID,unblock:true});break;}},deleteMessageDone(e,data){var url=ipsSettings['baseURL']+'?app=core&module=messaging&controller=messenger'
window.location=url;},moveConversation(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
var self=this;var realName=$('#elConvoMove_menu').find('[data-ipsMenuValue="'+data.selectedItemID+'"] a').html();ips.ui.alert.show({type:'confirm',icon:'question',message:ips.getString('conversationMove',{name:realName}),callbacks:{ok(){self.trigger('moveMessage.messages',{id:self._currentMessageID,folder:data.selectedItemID});}}});},deleteConversation(e){e.preventDefault();var self=this;ips.ui.alert.show({type:'confirm',icon:'question',message:ips.getString('messagesDelete'),subText:ips.getString('messagesDeleteSubText'),callbacks:{ok(){self.trigger('deleteMessage.messages',{id:self._currentMessageID});}}});},loadMessageLoading(e,data){this.cleanContents();this.scope.html($('<div/>').addClass('ipsLoading').html('&nbsp;').css({minHeight:'150px'}));},loadMessageDone(e,data){this.scope.html(data.response);$(document).trigger('contentChange',[this.scope]);},paginationClicked(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}},selectedMessage(e,data){this.trigger('loadMessage.messages',{messageID:data.messageID,messageURL:data.messageURL,messageTitle:data.messageTitle});this._currentMessageID=data.messageID;},stateChange(){const state=ips.utils.history.getState('messages');if(state?.controller!=='messages'){return;}
if(state.id==null){this.cleanContents();this.scope.html(ips.templates.render('messages.view.placeholder'));this._currentMessageID=null;this._currentPage=null;return;}
if(state.id!==this._currentMessageID){this.trigger('fetchMessage.messages',{id:state.id,page:state.page||1});ips.utils.analytics.trackPageView(window.location.href);this._currentMessageID=state.id;this._currentPage=state.page||1;}else if(state.page!==this._currentPage){this.trigger('fetchMessage.messages',{id:this._currentMessageID,page:state.page});this._currentPage=state.page;}},setInitialMessage(e,data){this._currentMessageID=data.messageID;},getFolder(){this.cleanContents();this.scope.html(ips.templates.render('messages.view.placeholder'));ips.utils.anim.go('fadeIn',this.scope);},});}(jQuery,_));;
;(function($,_){"use strict";ips.controller.register('core.front.messages.main',{_currentMessageID:null,_ready:{},_protectedFolders:['myconvo'],_params:{'sortBy':'mt_last_post_time','filter':'all'},_currentFolder:null,initialize(){this.on('menuItemSelected','#elMessageFolders',this.changeFolder);this.on('menuItemSelected','#elFolderSettings',this.folderAction);this.on('click','[data-action="addFolder"]',this.addFolder);this.on(document,'addFolderLoading.messages renameFolderLoading.messages '+
'markFolderLoading.messages emptyFolderLoading.messages '+
'deleteMessageLoading.messages deleteMessagesLoading.messages moveMessageLoading.messages '+
'deleteFolderLoading.messages',this.folderActionLoading);this.on(document,'addFolderFinished.messages renameFolderFinished.messages '+
'markFolderFinished.messages emptyFolderFinished.messages '+
'deleteMessageFinished.messages deleteMessagesFinished.messages moveMessageFinished.messages '+
'deleteFolderFinished.messages',this.folderActionDone);this.on(document,'deleteFolderDone.messages deleteMessageDone.messages deleteMessagesDone.messages '+
'emptyFolderDone.messages moveMessageDone.messages',this.updateCounts);this.on(document,'addFolderDone.messages',this.addFolderDone);this.on(document,'renameFolderDone.messages',this.renameFolderDone);this.on(document,'markFolderDone.messages',this.markFolderDone);this.on(document,'emptyFolderDone.messages',this.emptiedFolder);this.on(document,'deleteFolderDone.messages',this.deletedFolder);this.on('setInitialMessage.messages',this.setInitialMessage);this.on('setInitialFolder.messages',this.setInitialFolder);this.on('changeSort.messages changeFilter.messages',this.updateParam);this.on('input','[data-role="moderation"]',this.moderationInput);this.applyModMenuSelection();this.on('loadMessage.messages',this.loadMessage);this.on('changePage.messages',this.changePage);this.on(document,'controllerReady',this.controllerReady);this.on(document,'openDialog','#elAddFolder',this.addFolderDialogOpen);this.on(document,'openDialog','#elFolderRename',this.renameFolderDialogOpen);this.on(window,'historychange:messages',this.stateChange);},controllerReady(e,data){this._ready[data.controllerType]=true;if(this._ready['messages.list']&&this._ready['messages.view']&&data.controllerType=='core.front.messages.list'||data.controllerType=='core.front.messages.view'){this.trigger('messengerReady.messages');}},setInitialMessage(e,data){this._currentMessageID=data.messageID;},setInitialFolder(e,data){Debug.log(data);this._currentFolder=data.folderID;},changePage(e,data){this._updateURL({id:data.id,page:data.pageNo},{id:data.id,page:data.pageNo});},folderAction(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
if(this._currentFolder==null){}
if(_.indexOf(this._protectedFolders,this._currentFolder)!==-1&&_.indexOf(['delete','rename'],data.selectedItemID)!==-1){return;}
switch(data.selectedItemID){case'markRead':this._actionMarkRead(data);break;case'delete':this._actionDelete(data);break;case'empty':this._actionEmpty(data);break;case'rename':this._actionRename(data);break;}},folderActionLoading(e,data){var loading=this.scope.find('[data-role="loadingFolderAction"]');ips.utils.anim.go('fadeIn',loading);},folderActionDone(e,data){var loading=this.scope.find('[data-role="loadingFolderAction"]');ips.utils.anim.go('fadeOut',loading);},addFolder(e){var button=$(e.currentTarget);if(ips.ui.dialog.getObj(button)){ips.ui.dialog.getObj(button).show();}else{button.ipsDialog({content:'#elAddFolder_content',title:ips.getString('addFolder'),size:'narrow'});}},addFolderDone(e,data){var newItem=ips.templates.render('messages.main.folderMenu',{key:data.key,count:0,name:data.folderName});$('#elMessageFolders_menu').find('[data-ipsMenuValue]').last().after(newItem);$('#elMessageFolders_menu').find('[data-ipsMenuValue="'+data.key+'"]').click();},renameFolderDone(e,data){var realFolderName=this._getRealFolder(data.folder);$('#elMessageFolders_menu').find('[data-ipsMenuValue="'+data.folder+'"]').find('[data-role="folderName"]').text(data.folderName);this.scope.find('[data-role="currentFolder"]').text(data.folderName);ips.ui.flashMsg.show(ips.getString('renamedTo',{folderName:realFolderName,newFolderName:data.folderName}));},markFolderDone(e,data){var realFolderName=this._getRealFolder(data.folder);ips.ui.flashMsg.show(ips.getString('messengerMarked',{folderName:realFolderName}));},emptiedFolder(e,data){var menuItem=$('#elMessageFolders_menu').find('[data-ipsMenuValue="'+data.folder+'"]');menuItem.find('.ipsMenu_itemCount').html('0');this.trigger('loadFolder',{folder:this._currentFolder,sortBy:this._params['sortBy'],filter:this._params['filter']});},deletedFolder(e,data){this.scope.find('#elMessageFolders_menu').find('[data-ipsMenuValue="'+data.folder+'"]').remove().end().find('[data-ipsMenuValue="myconvo"]').click();ips.ui.flashMsg.show(ips.getString('folderDeleted'));},loadMessage(e,data){if(!data.messageID){return;}
this._newMessageID=data.messageID;this._updateURL({id:data.messageID,url:data.messageURL},{},data.messageTitle);},updateParam(e,data){if(!_.isUndefined(data.param)&&!_.isUndefined(data.value)){this._params[data.param]=data.value;}
this._updateURL(false,this._params);},changeFolder(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
var folderID=data.selectedItemID;var folderURL=data.menuElem.find('[data-ipsMenuValue="'+data.selectedItemID+'"] a').attr('href');var folderName=data.menuElem.find('[data-ipsMenuValue="'+data.selectedItemID+'"]').find('[data-role="folderName"]').text();if(_.isUndefined(folderID)){return;}
this._currentMessageID=null;this.scope.find('[data-ipsFilterBar]').trigger('switchTo.filterBar',{switchTo:'filterBar'});this._updateURL(_.extend({folder:folderID,url:folderURL},this._params),{folder:folderID,id:null,page:null},folderName);},addFolderDialogOpen(e,data){$(data.dialog).find('input[type="text"]').attr('data-folderID',this._currentFolder).val('').focus();},renameFolderDialogOpen(e,data){var realFolderName=this._getRealFolder(this._currentFolder);$(data.dialog).find('[data-role="folderName"]').attr('data-folderID',this._currentFolder).val(_.unescape(realFolderName)).focus();},stateChange(){const state=ips.utils.history.getState('messages')
if(state?.controller==='messages'&&state.folder!==this._currentFolder){this._updateFolder(state.folder);}},_updateURL(urlParams,newValues,newTitle){let url='';const title=newTitle||document.title;if(urlParams===false){url=window.location.href;if(window.location.hash){url=url.substr(0,url.length-window.location.hash.length);}}else if(urlParams.url){url=urlParams.url;}else{url=[];url.push('?app=core&module=messaging&controller=messenger');_.each(urlParams,function(value,idx){if(idx!='page'||(idx=='page'&&value!=1)){url.push(idx+"="+value);}});url=url.join('&');}
const defaultObj={id:this._newMessageID,folder:this._currentFolder,params:this._params,controller:'messages',};ips.utils.history.pushState({...defaultObj,...(newValues||{})},'messages',url);document.title=title},updateCounts(e,data){this.scope.find('[data-role="quotaTooltip"]').attr('data-ipsTooltip-label',data.quotaText).find('[data-role="quotaWidth"]').val(parseInt(data.quotaPercent)).end().find('[data-role="quotaValue"]').text(parseInt(data.quotaPercent));$('#elMessageFolders_menu').find('[data-ipsMenuValue]').each(function(){if(data.counts){$(this).find('.ipsMenu_itemCount').text(parseInt(data.counts[$(this).attr('data-ipsMenuValue')]?data.counts[$(this).attr('data-ipsMenuValue')]:'0'));}});},_updateFolder(newFolder){var folderName=$('[data-ipsMenuValue="'+newFolder+'"]').find('[data-role="folderName"]').text();var self=this;$('#elFolderSettings_menu').find('.ipsMenu_item').removeClass('ipsMenu_itemDisabled').show();$('#elFolderSettings_menu .ipsMenu_item a').each(function(){$(this).attr('href',$(this).attr('href').replace('&folder='+self._currentFolder,'&folder='+newFolder));});if(_.indexOf(this._protectedFolders,newFolder)!==-1){$('#elFolderSettings_menu').find('[data-ipsMenuValue="delete"], [data-ipsMenuValue="rename"]').addClass('ipsMenu_itemDisabled').hide();}
this.scope.find('[data-role="currentFolder"]').text(folderName);this._currentFolder=newFolder;},_actionRename(data){var dialog=$('#elFolderSettings_menu').find('[data-ipsMenuValue="rename"]');if(ips.ui.dialog.getObj(dialog)){ips.ui.dialog.getObj(dialog).show();}else{dialog.ipsDialog({content:'#elFolderRename_content',title:ips.getString('renameFolder'),size:'narrow'});}},_actionDelete(data){var self=this;ips.ui.alert.show({type:'confirm',icon:'question',message:ips.getString('messengerDeleteConfirm'),subText:ips.getString('cantBeUndone'),callbacks:{ok(){self.trigger('deleteFolder.messages',{folder:self._currentFolder});}}});},_actionMarkRead(data){var realFolderName=this._getRealFolder(this._currentFolder);var self=this;ips.ui.alert.show({type:'confirm',icon:'question',message:ips.getString('messengerMarkRead',{folderName:realFolderName}),callbacks:{ok(){self.trigger('markFolder.messages',{folder:self._currentFolder});}}});},_actionEmpty(data){var realFolderName=this._getRealFolder(this._currentFolder);var self=this;ips.ui.alert.show({type:'confirm',icon:'question',message:ips.getString('messengerDeleteContents',{folderName:realFolderName}),subText:ips.getString('cantBeUndone'),callbacks:{ok(){self.trigger('emptyFolder.messages',{folder:self._currentFolder});}}});},_getRealFolder(folder){var menuItem=$('#elMessageFolders_menu').find('[data-ipsMenuValue="'+folder+'"]');return menuItem.find('[data-role="folderName"]').html();},moderationInput(e){const currentData=ips.utils.db.get('messages','mod_menu_checked')||{};const id=e.target.name;if(!id){return;}
if(e.target.checked){currentData[id]=e.target.dataset.actions||"";}else{delete currentData[id];}
ips.utils.db.set('messages','mod_menu_checked',currentData);},applyModMenuSelection(){const currentData=ips.utils.db.get('messages','mod_menu_checked');const pageAction=this.elem.querySelector('[data-ipspageaction]');if(pageAction&&currentData&&typeof currentData==="object"&&Object.keys(currentData).length){const messageSelector=[...Object.keys(currentData)].map(id=>`[name="${id}"]`).join(',');const remainingIds=new Set(Object.keys(currentData));this.elem.querySelectorAll(`input[data-role="moderation"]:is(${messageSelector})`).forEach(el=>{remainingIds.delete(el.name);el.checked=true;$(el).trigger('change');});if(remainingIds.size){remainingIds.forEach(id=>{$(pageAction).trigger("addManualItem.pageAction",{id:id,actions:typeof currentData[id]==="string"?currentData[id]:''})});$(this.elem.querySelector('[data-ipsautocheck]')).trigger('setInitialCount.autoCheck',{count:remainingIds.size})}}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.messages.list',{_messageList:null,_searchTimer:null,_currentFolder:null,_currentMessageID:null,_currentOptions:{sortBy:'mt_last_post_time',filter:'all'},_infScrollURL:null,initialize(){this.on(document,'messengerReady.messages',this.messengerReady);this.on('menuItemSelected','#elSortByMenu',this.changeSort);this.on('menuItemSelected','#elFilterMenu',this.changeFilter);this.on('menuItemSelected','#elSearchTypes',this.selectedMenuItem);this.on('click','[data-messageid]',this.clickMessage);this.on('submit','[data-role="moderationTools"]',this.moderationSubmit);this.on('input','[data-role="messageSearchText"]',this.inputSearch);this.on('click','[data-action="messageSearchCancel"]',this.cancelSearch);this.on(document,'loadFolderDone.messages',this.loadFolderDone);this.on(document,'loadFolderLoading.messages, searchFolderLoading.messages',this.loadFolderLoading);this.on(document,'loadFolderFinished.messages',this.loadFolderFinished);this.on(document,'searchFolderLoading.messages',this.searchFolderLoading);this.on(document,'searchFolderDone.messages',this.searchFolderDone);this.on(document,'searchFolderFinished.messages',this.searchFolderFinished);this.on(document,'markFolderDone.messages',this.markFolderDone);this.on(document,'deleteMessagesDone.messages',this.deleteMessagesDone);this.on(document,'loadMessageDone.messages',this.markMessageRead);this.on(document,'deleteMessageDone.messages',this.deleteMessageDone);this.on(document,'moveMessageDone.messages',this.moveMessageDone);this.on(document,'addToCommentFeed',this.newMessage);this.on(document,'deletedComment.comment',this.deletedMessage);this.on(window,'historychange:messages',this.stateChange);this.setup();},setup(){this._messageList=this.scope.find('[data-role="messageList"]');this._currentFolder=this.scope.attr('data-folderID');this.trigger('setInitialFolder.messages',{folderID:this._currentFolder});},moderationSubmit(e,data){e.preventDefault();var self=this;var form=this.scope.find('[data-role="moderationTools"]');var count=parseInt(this.scope.find('[data-role="moderation"]:checked').length);if(this.scope.find('[data-role="pageActionOptions"]').find('select option:selected').val()=='move'){var dialog=ips.ui.dialog.create({remoteVerify:false,size:'narrow',remoteSubmit:false,title:ips.getString('messagesMove'),url:form.attr('action')+'&do=moveForm&ids='+_.map(self.scope.find('[data-role="moderation"]:checked'),function(item){return $(item).closest('[data-messageid]').attr('data-messageid');}).join(',')});dialog.show();}else{ips.ui.alert.show({type:'confirm',icon:'question',message:(count>1)?ips.pluralize(ips.getString('messagesDeleteMany'),count):ips.getString('messagesDelete'),subText:(count>1)?ips.getString('messagesDeleteManySubText'):ips.getString('messagesDeleteSubText'),callbacks:{ok(){var ids=_.map(self.scope.find('[data-role="moderation"]:checked'),function(item){return $(item).closest('[data-messageid]').attr('data-messageid');});self.trigger('deleteMessages.messages',{id:ids});}}});}},deleteMessagesDone(e,data){var selector=_.map(data.id,function(item){return'[data-messageid="'+item+'"]';}).join(',');var self=this;var messages=this._messageList.find(selector);if(messages.length){messages.slideUp({complete(){messages.remove();if(data.id.indexOf(self._currentMessageID)!==-1){self._currentMessageID=null;if(self._messageList.find('[data-messageid]').length){self._messageList.find('[data-messageid]').first().click();}else{self.trigger('getFolder',{folderID:self._currentFolder});}}
self._resetListActions();},queue:false}).fadeOut({queue:false});}},inputSearch(e){clearTimeout(this._searchTimer);this._searchTimer=setTimeout(_.bind(this._startSearch,this),500);},searchFolderLoading(e,data){this.scope.find('[data-role="messageSearchText"]').addClass('ipsField_loading');},searchFolderDone(e,data){this._messageList.html(data.data).show().end().find('[data-role="messageListPagination"]').html(data.pagination).end().find('[data-role="loading"]').attr('hidden',true).end().find('[data-role="messageListFilters"]').attr('hidden',true);this.scope.find('[data-action="messageSearchCancel"]').removeAttr('hidden');if(this.scope.is('[data-ipsInfScroll]')){var params=decodeURIComponent($.param(ips.utils.form.serializeAsObject($('[data-role="messageSearch"]'))));var base=this.scope.find('#elMessageList > form').attr('action');this._infScrollURL=this.scope.attr('data-ipsInfScroll');this.scope.attr('data-ipsInfScroll-url',base+'&'+params+'&folder='+this._currentFolder);this.scope.trigger('refresh.infScroll');}
$(document).trigger('contentChange',[this._messageList]);this._resetListActions();},searchFolderFinished(e,data){this.scope.find('[data-role="messageSearchText"]').removeClass('ipsField_loading');},cancelSearch(e){if(!_.isUndefined(e)){e.preventDefault();}
this._resetSearch();this._getFolder(this._currentFolder);},deletedReply(e,data){var count=this._messageList.find('[data-messageid="'+data.messageID+'"] [data-role="replyCount"]').text();this._messageList.find('[data-messageid="'+data.messageID+'"] [data-role="replyCount"]').text(parseInt(count)-1);},updateReplyCount(e,data){this._messageList.find('[data-messageid="'+data.messageID+'"] [data-role="replyCount"]').text(data.count);},markFolderDone(e,data){if(data.folder==this._currentFolder){this._messageList.find('[data-messageid]').removeAttr('data-ips-unread').find('.ipsIndicator').remove();}},deleteMessageDone(e,data){var message=this._messageList.find('[data-messageid="'+data.id+'"]');if(message.length){ips.utils.anim.go('fadeOutDown',message).done(function(){message.remove();});this._currentMessageID=null;}},moveMessageDone(e,data){var message=this._messageList.find('[data-messageid="'+data.id+'"]');var next=null;if(this._currentMessageID==data.id){if(message.prev('[data-messageid]').length){next=message.prev('[data-messageid]');}else if(message.next('[data-messageid]').length){next=message.next('[data-messageid]');}}
if(message.length&&data.to!=this._currentFolder){ips.utils.anim.go('fadeOutDown',message).done(function(){message.remove();});this._currentMessageID=null;}
ips.ui.flashMsg.show(ips.getString('conversationMoved'));if(next){next.click();}},loadFolderDone(e,data){this.scope.attr('data-ipsInfScroll-url',data.listBaseUrl).find('#elMessageList').scrollTop(0);this._messageList.html(data.data).show().end().find('[data-role="messageListPagination"]').html(data.pagination).end().find('[data-role="loading"]').attr('hidden',true);this.scope.trigger('refresh.infScroll');$(document).trigger('contentChange',[this._messageList]);this._resetListActions();},loadFolderLoading(e,data){if(!this.scope.find('[data-role="loading"]').length){this._messageList.after($('<div/>').addClass('ipsLoading').html('&nbsp;').css({minHeight:'150px'}).attr('data-role','loading'));}
this._messageList.hide();this._hideEmpty();this.scope.find('[data-role="loading"]').removeAttr('hidden');},loadFolderFinished(e,data){this._messageList.show();this._resetSearch();},messengerReady(){this._currentMessageID=this._messageList.find('.ipsData__item--active').attr('data-messageid');this.trigger('setInitialMessage.messages',{messageID:this._currentMessageID});},clickMessage(e){if($(e.target).is('input[type="checkbox"]')){return;}
e.preventDefault();var messageID=$(e.currentTarget).attr('data-messageid');var messageURL=$(e.currentTarget).find('[data-role="messageURL"]').attr('href');var messageTitle=$(e.currentTarget).find('[data-role="messageURL"]').text();this.trigger('selectedMessage.messages',{messageID:messageID,messageURL:messageURL,messageTitle:messageTitle});this.trigger('switchTo.filterBar',{switchTo:'filterContent'});this._selectMessage(messageID);},newMessage(e,data){this._updateRow(data.feedID.substr(data.feedID.indexOf('-')+1));},deletedMessage(e,data){var feedId=$(e.target).closest('[data-feedid]').attr('data-feedid');this._updateRow(feedId.substr(feedId.indexOf('-')+1));},_updateRow:function(conversationId){var scope=$(this.scope);ips.getAjax()(ips.getSetting('baseURL')+'index.php?app=core&module=messaging&controller=messenger&id='+conversationId+'&getRow=1').done(function(response){scope.find('[data-messageid="'+conversationId+'"]').replaceWith(response);$(document).trigger('contentChange',[scope]);});},changeSort(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
var sort=data.selectedItemID;if(sort){this.trigger('changeSort.messages',{param:'sortBy',value:sort});}},changeFilter(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}
var filter=data.selectedItemID;if(filter){this.trigger('changeFilter.messages',{param:'filter',value:filter});}},stateChange(){const state=ips.utils.history.getState('messages')||{}
if(state.controller!=='messages'){return;}
let newFilters=false;if(typeof state.params==='object'&&(state.params.sortBy!==this._currentOptions.sortBy||state.params.filter!==this._currentOptions.filter)){this._currentOptions.sortBy=state.params.sortBy;this._currentOptions.filter=state.params.filter;newFilters=true;}
if(state.folder!==this._currentFolder||newFilters){this._getFolder(state.folder);}
if(state.mid!==this._currentMessageID){if(Array.isArray(state.mid)){this._selectMessages(state.mid);}else{this._selectMessage(state.mid);}}},markMessageRead:function(e,data){this._messageList.find('[data-messageid="'+data.id+'"]').removeAttr('data-ips-unread');},_startSearch(e){var serialized=ips.utils.form.serializeAsObject($('[data-role="messageSearch"]'));if(!serialized.q.length){this.cancelSearch();return;}
if(!serialized.q.length){this.cancelSearch();return;}
var gotSomething=false;_.each(['topic','post','recipient','sender'],function(item){if(_.has(serialized.search,item)){gotSomething=true;}});if(!gotSomething){var self=this;ips.ui.alert.show({type:'alert',icon:'warn',message:ips.getString('messageSearchFail'),subText:ips.getString('messageSearchFailSubText'),callbacks:{ok(){self._resetSearch();return false;}}});}else{this.trigger('searchFolder.messages',_.extend({folder:this._currentFolder},serialized));}},_resetSearch(){this.scope.find('[data-role="messageSearchText"]').removeClass('ipsField_loading').val('');this.scope.find('[data-action="messageSearchCancel"]').attr('hidden',true);this.scope.find('[data-role="messageListFilters"]').removeAttr('hidden');this._resetListActions();this.scope.attr('data-ipsInfScroll',this._infScrollURL);this.scope.trigger('refresh.infScroll');},_selectMessage(id){this._messageList.find('[data-messageid]').removeClass('ipsData__item--active').end().find('[data-messageid="'+id+'"]').addClass('ipsData__item--active');this._currentMessageID=id;},_selectMessages(IDs){var self=this;this._messageList.find('[data-messageid]').removeClass('ipsData__item--active');_.each(IDs,function(id){self._messageList.find('[data-messageid="'+id+'"]').addClass('ipsData__item--active');});this._currentMessageID=IDs;},_getFolder(newFolder){this.trigger('loadFolder.messages',{folder:newFolder,filter:this._currentOptions.filter,sortBy:this._currentOptions.sortBy});this._currentFolder=newFolder;},_hideEmpty(){this.scope.find('[data-role="emptyMsg"]').hide();},_resetListActions(){try{ips.ui.pageAction.getObj(this.scope.find('[data-ipsPageAction]')).reset();ips.ui.autoCheck.getObj(this.scope.find('[data-ipsAutoCheck]')).refresh();}catch(err){}},selectedMenuItem(e,data){if(data.originalEvent){data.originalEvent.preventDefault();}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.messages.folderDialog',{_events:{add:'addFolder',rename:'renameFolder'},initialize:function(){this.on('submit','form',this.submitName);},submitName:function(e){e.preventDefault();e.stopPropagation();var type=this.scope.attr('data-type');var field=this.scope.find('[data-role="folderName"]');var val=field.val();var folderID=field.attr('data-folderID');this.trigger(this._events[type]+'.messages',{folder:folderID,name:val});this.trigger('closeDialog');}});}(jQuery,_));;