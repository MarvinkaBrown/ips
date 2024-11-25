ips.templates.set('nexus.store.images',`
	<div class='ipsUploader__row ipsUploader__row--image ipsAttach {{#done}}ipsAttach_done{{/done}}' id='{{id}}' data-role='file' data-fileid='{{id}}' data-fullsizeurl='{{imagesrc}}' data-thumbnailurl='{{thumbnail}}' data-fileType='image'>
		<div class='ipsUploader__rowPreview' data-role='preview'>
			{{#thumb}}
				{{{thumb}}}
			{{/thumb}}
			<div class='ipsUploader__rowPreview__generic' {{#thumb}}style='display: none'{{/thumb}}>
				<i class='fa-solid fa-{{extIcon}}'></i>
			</div>
		</div>
		<div class='ipsUploader_rowMeta'>
			<h2 class='ipsUploader_rowTitle' data-role='title'>{{title}}</h2>
			<p class='ipsUploader_rowDesc'>
				{{size}} {{#statusText}}&middot; <span class='i-color_soft' data-role='status'>{{statusText}}</span>{{/statusText}}
			</p>
			{{#status}}<meter class='ipsMeter' data-role='progressbar' max='100'></meter>{{/status}}
		</div>
		<div data-role='deleteFileWrapper'>
			<input type='hidden' name='{{field_name}}_keep[{{id}}]' value='1'>
			<a href='#' data-role='deleteFile' class='ipsUploader__rowDelete' data-ipsTooltip title='{{#lang}}removeProductImage{{/lang}}'>
				&times;
			</a>
		</div>
		<label class='cNexusPrimaryRadio' data-ipsTooltip title='{{#lang}}makePrimaryProductImage{{/lang}}'>
			<input type='radio' class='ipsInput ipsInput--toggle' name='{{field_name}}_primary_image' value='{{id}}' {{#default}}checked{{/default}}>
			{{#lang}}makePrimary{{/lang}}
		</label>
	</div>
`);ips.templates.set('nexus.store.imagesWrapper',`
	<div class='ipsUploader__container ipsUploader__container--images'>{{{content}}}</div>
`);;
;(function($,_,undefined){"use strict";ips.controller.register('nexus.admin.store.productoptions',{initialize:function(){var self=this;this.on('change','[data-role="field"]',this.refresh);$('input[name="p_renews_checkbox"]').change(function(){self.refresh();});if($('input[name="p_images_primary_image"]').length){$('input[name="p_images_primary_image"]:first').attr('checked',true);}
this.refresh();},refresh:function(){var ids=[];$(this.scope).find('[data-role="field"]:checked').each(function(){ids.push($(this).attr('data-id'));});if($('input[name="p_renews_checkbox"]').is(':checked')){var renews=1;}else{var renews=0;}
var scope=$(this.scope);ips.getAjax()(scope.attr('data-url')+'&fields='+ids.join(',')+'&renews='+renews).done(function(response){scope.find('[data-role="table"]').html(response);$(document).trigger('contentChange',[scope]);});},});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('nexus.admin.store.productselector',{initialize:function(){this.url=$(this.scope).attr('data-url');this.on('click','[data-role="group"]',this.expandCollapse);this.on('click','[data-role="product"]',this.increaseQty);},expandCollapse:function(e){var row=$(e.currentTarget);var list=row.next();if(row.hasClass('ipsTree_open')){row.removeClass('ipsTree_open');list.hide();}else{row.addClass('ipsTree_open');list.show();if(!list.data('_childrenLoaded')){list.html(ips.templates.render('core.trees.childWrapper',{content:ips.templates.render('core.trees.loadingRow')}));ips.getAjax()(this.url+'&id='+row.attr('data-groupId')).done(function(response){list.html(response);list.data('_childrenLoaded','true');})}}},increaseQty:function(e){if(!$(e.target).is('input')){$(e.currentTarget).find('input').val(parseInt($(e.currentTarget).find('input').val())+1);}}});}(jQuery,_));;