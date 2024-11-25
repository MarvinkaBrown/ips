ips.templates.set('follow.frequency',"	{{#hasNotifications}}		<i class='fa-solid fa-bell'></i>	{{/hasNotifications}}	{{^hasNotifications}}		<i class='fa-regular fa-bell-slash'></i>	{{/hasNotifications}}	{{text}}");;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.manageFollowed',{initialize:function(){$(document).on('followingItem',_.bind(this.followingItemChange,this));this.setup();},setup:function(){this._followID=this.scope.attr('data-followID');},followingItemChange:function(e,data){if(data.feedID!=this._followID){return;}
if(!_.isUndefined(data.unfollow)){this.scope.find('[data-role="followDate"], [data-role="followFrequency"]').html('');this.scope.find('[data-role="followAnonymous"]').addClass('ipsHide');this.scope.find('[data-role="followButton"]').addClass('ipsButton--disabled');this.scope.addClass('i-opacity_4');return;}
this.scope.find('[data-role="followAnonymous"]').toggleClass('ipsHide',!data.anonymous);if(data.notificationType){this.scope.find('[data-role="followFrequency"]').html(ips.templates.render('follow.frequency',{hasNotifications:(data.notificationType!=='none'),text:ips.getString('followFrequency_'+data.notificationType)}));}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.metaTagEditor',{_changed:false,initialize:function(){this.on('click','[data-action="addMeta"]',this.addMetaBlock);this.on('click','[data-action="deleteMeta"]',this.removeMetaBlock);this.on('click','[data-action="deleteDefaultMeta"]',this.removeDefaultMeta);this.on('click','[data-action="restoreMeta"]',this.restoreDefaultMeta);this.on('change','input, select',this.changed);this.on('submit','form',this.formSubmit);this.on(window,'beforeunload',this.beforeUnload);this.on('change','[data-role="metaTagChooser"]',this.toggleNameField);this.setup();},setup:function(){this.scope.css({zIndex:"10000"});},toggleNameField:function(e){if($(e.currentTarget).val()=='other'){$(e.currentTarget).closest('ul').find('[data-role="metaTagName"]').show();}
else
{$(e.currentTarget).closest('ul').find('[data-role="metaTagName"]').hide();}},restoreDefaultMeta:function(e){var tag=$(e.currentTarget).attr('data-tag');var copy=this.scope.find('[data-role="metaTemplate"]').clone().attr('data-role','metaTagRow').hide();if(tag=='robots'||tag=='keywords'||tag=='description'){copy.find('select[name="meta_tag_name[]"]').val(tag);}
else
{copy.find('select[name="meta_tag_name[]"]').val('other');copy.find('[name="meta_tag_name_other[]"]').val(tag).parent().removeClass('ipsHide');}
if(this.scope.find('input[name="defaultMetaTag['+tag+']"]')){copy.find('[name="meta_tag_content[]"]').val(this.scope.find('input[name="defaultMetaTag['+tag+']"]').val());}
copy.find('[data-action="deleteMeta"]').attr('data-action','deleteDefaultMeta');$('#elMetaTagEditor_defaultTags').append(copy);ips.utils.anim.go('fadeIn',copy);$(document).trigger('contentChange',[this.scope]);this._doMetaRemoval(e);},removeDefaultMeta:function(e){if($(e.currentTarget).siblings('select').first().val()=='other'){var name=$(e.currentTarget).closest('ul').find('input[name="meta_tag_name_other[]"]').val();}
else
{var name=$(e.currentTarget).siblings('select').first().val();}
$(e.currentTarget).closest('form').find('input').first().after("<input type='hidden' name='deleteDefaultMeta[]' value='"+name+"'>");this.removeMetaBlock(e,false);var string=ips.getString('meta_tag_deleted',{tag:name});var copy=this.scope.find('[data-role="metaDefaultDeletedTemplate"]').clone().attr('data-role','metaTagRow').hide();copy.find('[data-role="metaDeleteMessage"]').html(string);copy.find('[data-action="restoreMeta"]').attr('data-tag',name);$('#elMetaTagEditor_defaultTags').find('.i-background_3').after(copy);ips.utils.anim.go('fadeIn',copy);$(document).trigger('contentChange',[this.scope]);this.changed();this._showHideNoTagsMessage();},removeMetaBlock:function(e,restoreDefault){if(_.isUndefined(restoreDefault)){restoreDefault=true;}
if($(e.currentTarget).siblings('select').first().val()=='other'){var tag=$(e.currentTarget).closest('ul').find('input[name="meta_tag_name_other[]"]').val();}
else
{var tag=$(e.currentTarget).siblings('select').first().val();}
if(this.scope.find('input[name="defaultMetaTag['+tag+']"]').length&&restoreDefault){$(e.currentTarget).attr('data-tag',tag);this.restoreDefaultMeta(e);}
else
{this._doMetaRemoval(e);}},_doMetaRemoval:function(e){e.preventDefault();var elem=$(e.currentTarget).closest('[data-role="metaTagRow"]');elem.remove();ips.utils.anim.go('fadeOut',elem);this.changed();this._showHideNoTagsMessage();},_showHideNoTagsMessage:function(){if($('#elMetaTagEditor_customTags').find('li[data-role="metaTagRow"]').length){$('#elMetaTagEditor_customTags').find('li[data-role="noCustomMetaTagsMessage"]').hide();}
else
{$('#elMetaTagEditor_customTags').find('li[data-role="noCustomMetaTagsMessage"]').show();}},formSubmit:function(e){var form=$(e.currentTarget);if(form.attr('data-noAjax')){return;}
e.preventDefault();var self=this;form.find('.ipsButton').prop('disabled',true).addClass('ipsButton--disabled');ips.getAjax()(form.attr('action'),{data:form.serialize(),type:'post'}).done(function(){ips.ui.flashMsg.show(ips.getString('metaTagsSaved'));form.find('.ipsButton').prop('disabled',false).removeClass('ipsButton--disabled');self._changed=false;if(form.find('[name="meta_tag_title"]').val()){document.title=form.find('[name="meta_tag_title"]').val();}
else
{document.title=self.scope.attr('data-defaultPageTitle');}}).fail(function(){form.attr('data-noAjax','true');form.submit();});},beforeUnload:function(){if(this._changed){return ips.getString('metaTagsUnsaved');}},addMetaBlock:function(e){e.preventDefault();var copy=this.scope.find('[data-role="metaTemplate"]').clone().attr('data-role','metaTagRow').hide();$('#elMetaTagEditor_customTags').append(copy);ips.utils.anim.go('fadeIn',copy);$(document).trigger('contentChange',[copy]);this.changed();this._showHideNoTagsMessage();},changed:function(e){this._changed=true;}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.notificationSettings',{initialize:function(){this.on('click','[data-action="enablePush"]',this.enablePush);this.on(document,'subscribePending.notifications',this.subscribePending);this.on(document,'subscribeSuccess.notifications',this.subscribeSuccess);this.on(document,'subscribeFail.notifications',this.subscribeFail);this.on(document,'permissionDenied.notifications',this.permissionDenied);this.on('click','[data-action="showNotificationSettings"]',this.showNotificationSettings);this.on('click','[data-action="closeNotificationSettings"]',this.closeNotificationSettings);this.on('change','[data-role="notificationSettingsWindow"]',this.saveNotificationSettings);this.on('change','#elBrowserNotifications',this.promptMe);this.setup();},setup:function(){this._showNotificationOptions();},_showNotificationOptions:function(){const pushElement=this.scope.find('[data-action="enablePush"]');if(ips.utils.notification.supported&&ips.utils.serviceWorker.supported){if(Notification.permission==='granted'){ips.utils.notification.getSubscription().then(subscription=>{if(!subscription){return;}
const enableLink=pushElement.contents();pushElement.html(ips.templates.render('core.notifications.checking'));const jsonSubscription=JSON.parse(JSON.stringify(subscription));const key=jsonSubscription.keys.p256dh;ips.getAjax()(ips.getSetting('baseURL')+'index.php?app=core&module=system&controller=notifications&do=verifySubscription',{type:'post',data:{key}}).done((response,status,jqXHR)=>{if(jqXHR.status===200){pushElement.html(ips.templates.render('core.notifications.success'));return;}
pushElement.html(enableLink);}).fail(()=>{pushElement.html(enableLink);});}).catch(err=>{Debug.error(err);pushElement.html(ips.templates.render('core.notifications.notSupported'));});}else if(Notification.permission==='denied'){pushElement.html(ips.templates.render('core.notifications.fail'));}}else{Debug.log("Notifications not supported");pushElement.html(ips.templates.render('core.notifications.notSupported'));}
pushElement.slideDown();},enablePush:function(e){e.preventDefault();ips.utils.notification.requestPermission();},subscribePending:function(e,data){this.scope.find('[data-action="enablePush"]').html(ips.templates.render('core.notifications.pending')).show();},subscribeSuccess:function(e,data){this.scope.find('[data-action="enablePush"]').html(ips.templates.render('core.notifications.success')).show();},subscribeFail:function(e,data){this.scope.find('[data-action="enablePush"]').html(ips.templates.render('core.notifications.fail')).show();},permissionDenied:function(){this.scope.find('[data-action="enablePush"]').html(ips.templates.render('core.notifications.fail')).show();},promptMe:function(e){if($(e.target).is(':checked')){ips.utils.cookie.unset('noBrowserNotifications');if(!ips.utils.notification.hasPermission()){ips.utils.notification.requestPermission();}else{ips.ui.flashMsg.show(ips.getString('saved'));}}else{ips.utils.cookie.set('noBrowserNotifications',true,true);ips.ui.flashMsg.show(ips.getString('saved'));}},_showNotificationChoice:function(){this.scope.find('[data-role="browserNotifyInfo"]').show();var type=ips.utils.notification.permissionLevel();switch(type){case'denied':$('#elBrowserNotifications').prop('checked',false).prop('disabled',true);this.scope.find('[data-role="browserNotifyDisabled"]').show();break;case'granted':$('#elBrowserNotifications').prop('checked',!ips.utils.cookie.get('noBrowserNotifications'));break;default:break;}},showNotificationSettings:function(e){e.preventDefault();var target=$(e.currentTarget);var expandedContainer=target.parent().find('[data-role="notificationSettingsWindow"]');this.scope.find('.cNotificationTypes__row--selected').removeClass('cNotificationTypes__row--selected');this.scope.find('[data-action="showNotificationSettings"]').show();this.scope.find('[data-role="notificationSettingsWindow"]').hide();target.parent().addClass('cNotificationTypes__row--selected');target.find('.cNotificationSettings_expand').addClass('ipsLoading ipsLoading--tiny').find('i').addClass('ipsHide');ips.getAjax()(target.attr('href')).done(function(response){expandedContainer.html(response).show();target.hide();target.find('.cNotificationSettings_expand').removeClass('ipsLoading').find('i').removeClass('ipsHide');}).fail(function(){window.location=target.attr('href');})},closeNotificationSettings:function(e){e.preventDefault();this.scope.find('.cNotificationTypes__row--selected').removeClass('cNotificationTypes__row--selected');this.scope.find('[data-action="showNotificationSettings"]').show();this.scope.find('[data-role="notificationSettingsWindow"]').hide();},saveNotificationSettings:function(e){e.preventDefault();var target=$(e.target);var form=target.closest('form');var container=form.closest('[data-role="notificationSettingsWindow"]');var containerParent=container.closest('.cNotificationTypes__row');var closeIcon=container.find('[data-action="closeNotificationSettings"]');closeIcon.addClass('ipsLoading ipsLoading--tiny').text('');ips.getAjax()(form.attr('action'),{data:form.serialize(),type:'post'}).done(function(response){closeIcon.removeClass('ipsLoading').html('&times;');containerParent.find('[data-action="showNotificationSettings"]').html(response);ips.ui.flashMsg.show(ips.getString('saved'));});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.referrals',{initialize:function(){$('.cReferrer_copy').each(function(){$(this).hide();});ips.loader.get(['core/interface/clipboard/clipboard.min.js']).then(function(){if(ClipboardJS.isSupported()){$('.cReferrer_copy').each(function(){$(this).show();});var clipboard=new ClipboardJS('.cReferrer_copy');clipboard.on('success',function(e){ips.ui.flashMsg.show(ips.getString('copied'));e.clearSelection();});}else{$('.cReferrals_directLink_input').removeClass('ipsHide');$('.cReferrals_directLink_link').addClass('ipsHide');}});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.front.system.register',{usernameField:null,timers:{'username':null,'email':null},ajax:ips.getAjax(),popup:null,passwordBlurred:true,dirty:false,initialize:function(){this.on('keyup','#elInput_username',this.changeUsername);this.on('blur','#elInput_username',this.changeUsername);this.on('keyup','#elInput_password_confirm',this.confirmPassword);this.on('blur','#elInput_password_confirm',this.confirmPassword);this.on('click','a[data-ipsPbrCancel]',this.cancelPbr);this.setup();},setup:function(){this.usernameField=this.scope.find('#elInput_username');this.passwordField=this.scope.find('#elInput_password');this.confirmPasswordField=this.scope.find('#elInput_password_confirm');this.usernameField.after($('<span/>').attr('data-role','validationCheck'));this.confirmPasswordField.after($('<span/>').attr('data-role','validationCheck'));this.convertExistingErrors();},convertExistingErrors:function(){var fields=this.scope.find('#elInput_username, #elInput_password, #elInput_password_confirm');var self=this;fields.each(function(){var elem=$(this);var wrapper=elem.closest('.ipsFieldRow');if(!wrapper.hasClass('ipsFieldRow_error')){return;}
var message=wrapper.find('.i-color_warning').html();self._clearResult(elem);wrapper.find('[data-role="validationCheck"]').show().html(ips.templates.render('core.forms.validateFailText',{message:message}));elem.removeClass('ipsField_success').addClass('ipsField_error');});},cancelPbr:function(e){var url=$(e.target).closest('[data-ipsPbrCancel]').attr('href');e.preventDefault();e.stopPropagation();ips.ui.alert.show({type:'confirm',message:ips.getString('pbr_confirm_title'),subText:ips.getString('pbr_confirm_text'),icon:'warn',buttons:{ok:ips.getString('pbr_confirm_ok'),cancel:ips.getString('pbr_confirm_cancel')},callbacks:{ok:function(){window.location=url;},cancel:function(){return false;}}});},changeUsername:function(e){if(this.timers['username']){clearTimeout(this.timers['username']);}
if(this.usernameField.val().length>4||e.type!="keyup"){this.timers['username']=setTimeout(_.bind(this._doCheck,this,this.usernameField),700);}else{this._clearResult(this.usernameField);}},changePassword:function(e){if(this.timers['password']){clearTimeout(this.timers['password']);}
if(this.passwordField.val().length>2||e.type!="keyup"){this.timers['password']=setTimeout(_.bind(this._doPasswordCheck,this,this.passwordField),200);}else{this._clearResult(this.passwordField);}
this.confirmPassword();},confirmPassword:function(e){var resultElem=this.confirmPasswordField.next('[data-role="validationCheck"]');if(this.passwordField.val()&&this.passwordField.val()===this.confirmPasswordField.val()){resultElem.hide().html('');this.confirmPasswordField.removeClass('ipsField_error').addClass('ipsField_success');}else{this._clearResult(this.confirmPasswordField);}},_clearResult:function(field){field.removeClass('ipsField_error').removeClass('ipsField_success').next('[data-role="validationCheck"]').html('');field.closest('.ipsFieldRow').removeClass('ipsFieldRow_error').find('.i-color_warning, .ipsFieldRow__content br:last').remove();},_doCheck:function(field){var value=field.val();var resultElem=field.next('[data-role="validationCheck"]');var self=this;if(this.ajax&&this.ajax.abort){this.ajax.abort();}
field.addClass('ipsField_loading');this.ajax(ips.getSetting('baseURL')+'?app=core&module=system&controller=ajax&do=usernameExists',{dataType:'json',data:{input:encodeURIComponent(value)}}).done(function(response){if(response.result=='ok'){resultElem.hide().html('');field.removeClass('ipsField_error').addClass('ipsField_success');}else{resultElem.show().html(ips.templates.render('core.forms.validateFailText',{message:response.message}));field.removeClass('ipsField_success').addClass('ipsField_error');}}).fail(function(){}).always(function(){field.removeClass('ipsField_loading');});}});}(jQuery,_));;